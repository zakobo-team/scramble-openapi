<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\OpenApi;

use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ArrayType as OpenApiArrayType;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\MixedType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType as OpenApiObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\RouteInfo;
use Dedoc\Scramble\Support\Type\ObjectType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use Zakobo\JsonApiQuery\Documentation\JsonApiQueryDocumentation;
use Zakobo\JsonApiQuery\Documentation\JsonApiQueryDocumentationFactory;

class JsonApiCollectionOperationTransformer extends OperationExtension
{
    private const JSON_API_MEDIA_TYPE = 'application/vnd.api+json';

    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        $methodCall = $this->jsonApiCollectionCallFrom($routeInfo);
        $resourceClass = $methodCall !== null ? $this->resourceClassFrom($methodCall) : null;
        $modelClass = $methodCall !== null ? $this->modelClassFrom($methodCall->var) : null;

        if ($resourceClass === null || ! is_subclass_of($resourceClass, JsonApiResource::class)) {
            return;
        }

        $this->replaceSuccessResponse($operation, $resourceClass);

        if ($modelClass !== null) {
            $this->addJsonApiQueryParameters(
                $operation,
                app(JsonApiQueryDocumentationFactory::class)->for(new $modelClass, $resourceClass, Request::create('/')),
            );
        }
    }

    private function jsonApiCollectionCallFrom(RouteInfo $routeInfo): ?MethodCall
    {
        $actionNode = $routeInfo->actionNode();

        if (! $actionNode instanceof ClassMethod) {
            return null;
        }

        $methodCall = (new NodeFinder)->findFirst(
            $actionNode->stmts ?? [],
            fn (Node $node): bool => $node instanceof MethodCall
                && $node->name instanceof Node\Identifier
                && $node->name->toString() === 'jsonApiCollection',
        );

        return $methodCall instanceof MethodCall ? $methodCall : null;
    }

    /**
     * @return class-string<JsonApiResource>|null
     */
    private function resourceClassFrom(MethodCall $methodCall): ?string
    {
        $firstArgument = $methodCall->args[0]->value ?? null;

        if (! $firstArgument instanceof ClassConstFetch || $firstArgument->name->toString() !== 'class') {
            return null;
        }

        $class = $firstArgument->class;

        if (! $class instanceof Name) {
            return null;
        }

        $resolvedName = $class->getAttribute('resolvedName');
        $resourceClass = $resolvedName instanceof Name ? $resolvedName->toString() : $class->toString();

        return class_exists($resourceClass) ? $resourceClass : null;
    }

    /**
     * @return class-string<Model>|null
     */
    private function modelClassFrom(Node\Expr $expression): ?string
    {
        if ($expression instanceof MethodCall) {
            return $this->modelClassFrom($expression->var);
        }

        if (! $expression instanceof StaticCall || ! $expression->class instanceof Name) {
            return null;
        }

        $resolvedName = $expression->class->getAttribute('resolvedName');
        $class = $resolvedName instanceof Name ? $resolvedName->toString() : $expression->class->toString();

        return class_exists($class) && is_subclass_of($class, Model::class) ? $class : null;
    }

    /**
     * @param  class-string<JsonApiResource>  $resourceClass
     */
    private function replaceSuccessResponse(Operation $operation, string $resourceClass): void
    {
        $response = $this->successResponse($operation);
        $response->content = [];

        $response->setContent(
            self::JSON_API_MEDIA_TYPE,
            Schema::fromType($this->collectionDocumentType($resourceClass)),
        );
    }

    /**
     * @param  class-string<JsonApiResource>  $resourceClass
     */
    private function collectionDocumentType(string $resourceClass): OpenApiObjectType
    {
        return (new OpenApiObjectType)
            ->addProperty('data', (new OpenApiArrayType)->setItems(
                $this->openApiTransformer->transform(new ObjectType($resourceClass)),
            ))
            ->addProperty('links', (new OpenApiObjectType)->additionalProperties(new MixedType))
            ->addProperty('meta', (new OpenApiObjectType)->additionalProperties(new MixedType))
            ->setRequired(['data']);
    }

    private function successResponse(Operation $operation): Response
    {
        foreach ($operation->responses ?? [] as $response) {
            if ($response instanceof Response && (string) $response->code === '200') {
                return $response;
            }
        }

        $response = Response::make(200)->setDescription('Array of items');
        $operation->addResponse($response);

        return $response;
    }

    private function addJsonApiQueryParameters(Operation $operation, JsonApiQueryDocumentation $documentation): void
    {
        $existing = collect($operation->parameters)
            ->filter(fn (mixed $parameter): bool => $parameter instanceof Parameter && $parameter->in === 'query')
            ->map(fn (Parameter $parameter): string => $parameter->name)
            ->all();

        $parameters = collect($this->jsonApiQueryParameters($documentation))
            ->reject(fn (Parameter $parameter): bool => in_array($parameter->name, $existing, true))
            ->values()
            ->all();

        $operation->addParameters($parameters);
    }

    /**
     * @return list<Parameter>
     */
    private function jsonApiQueryParameters(JsonApiQueryDocumentation $documentation): array
    {
        $parameters = [];

        $parameters = [
            ...$parameters,
            ...$this->filterParameters($documentation->filterFields),
        ];

        if ($documentation->sortFields !== []) {
            $parameters[] = $this->enumArrayParameter(
                'sort',
                $this->sortValues($documentation->sortFields),
                $this->fieldDescription(
                    'Comma-separated JSON:API sort fields generated from the runtime resource query contract. Prefix a field with - for descending order.',
                    'Available sort fields',
                    $documentation->sortFields,
                ),
            );
        }

        if ($documentation->includePaths !== []) {
            $parameters[] = $this->enumArrayParameter(
                'include',
                $documentation->includePaths,
                $this->fieldDescription(
                    'Comma-separated JSON:API relationship include paths generated from the runtime resource query contract.',
                    'Available include paths',
                    $documentation->includePaths,
                ),
            );
        }

        $parameters = [
            ...$parameters,
            ...$this->includeFilterParameters($documentation->includeFilterFields),
        ];

        foreach ($documentation->fieldsets as $resourceType => $fields) {
            if ($fields === []) {
                continue;
            }

            $parameters[] = $this->enumArrayParameter(
                "fields[{$resourceType}]",
                $fields,
                $this->fieldDescription(
                    "Comma-separated sparse fieldset for {$resourceType}.",
                    'Available fields',
                    $fields,
                ),
            );
        }

        $parameters[] = Parameter::make('page[number]', 'query')
            ->setSchema(Schema::fromType((new IntegerType)->setMin(1)))
            ->description('Page number.');

        $pageSize = (new IntegerType)
            ->setMin($documentation->allowUnpaginated ? -1 : 1)
            ->setMax($documentation->maxPageSize);

        $parameters[] = Parameter::make('page[size]', 'query')
            ->setSchema(Schema::fromType($pageSize))
            ->description($documentation->allowUnpaginated
                ? 'Page size. Use -1 to request an unpaginated collection.'
                : "Page size. Maximum value is {$documentation->maxPageSize}.");

        return $parameters;
    }

    /**
     * @param  list<string>  $fields
     */
    private function fieldDescription(string $description, string $label, array $fields): string
    {
        return "{$description} {$label}: ".implode(', ', $fields).'.';
    }

    /**
     * @param  list<string>  $fields
     * @return list<Parameter>
     */
    private function filterParameters(array $fields): array
    {
        return array_map(
            fn (string $field): Parameter => Parameter::make("filter[{$field}]", 'query')
                ->setSchema(Schema::fromType(new StringType))
                ->description("JSON:API filter for {$field}. Supports scalar values and operator objects such as filter[{$field}][eq], filter[{$field}][gte], filter[{$field}][lte]."),
            $fields,
        );
    }

    /**
     * @param  list<string>  $fields
     * @return list<Parameter>
     */
    private function includeFilterParameters(array $fields): array
    {
        return array_map(
            fn (string $field): Parameter => Parameter::make("includeFilter[{$field}]", 'query')
                ->setSchema(Schema::fromType(new StringType))
                ->description("JSON:API include filter for {$field}. The related include path must also be present in include."),
            $fields,
        );
    }

    /**
     * @param  list<string>  $values
     */
    private function enumArrayParameter(string $name, array $values, string $description): Parameter
    {
        return tap(
            Parameter::make($name, 'query')
                ->setExplode(false)
                ->setSchema(Schema::fromType(
                    (new OpenApiArrayType)->setItems((new StringType)->enum($values)),
                ))
                ->description($description),
            fn (Parameter $parameter) => $parameter->setAttribute('isFlat', true),
        );
    }

    /**
     * @param  list<string>  $fields
     * @return list<string>
     */
    private function sortValues(array $fields): array
    {
        $values = [];

        foreach ($fields as $field) {
            $values[] = $field;
            $values[] = "-{$field}";
        }

        return $values;
    }
}
