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
use Dedoc\Scramble\Support\Generator\Types\ObjectType as OpenApiObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\RouteInfo;
use Dedoc\Scramble\Support\Type\ObjectType;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;

class JsonApiCollectionOperationTransformer extends OperationExtension
{
    private const JSON_API_MEDIA_TYPE = 'application/vnd.api+json';

    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        $resourceClass = $this->resourceClassFrom($routeInfo);

        if ($resourceClass === null || ! is_subclass_of($resourceClass, JsonApiResource::class)) {
            return;
        }

        $this->replaceSuccessResponse($operation, $resourceClass);
        $this->addJsonApiQueryParameters($operation);
    }

    /**
     * @return class-string<JsonApiResource>|null
     */
    private function resourceClassFrom(RouteInfo $routeInfo): ?string
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

        if (! $methodCall instanceof MethodCall) {
            return null;
        }

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
     * @param  class-string<JsonApiResource>  $resourceClass
     */
    private function replaceSuccessResponse(Operation $operation, string $resourceClass): void
    {
        $response = $this->successResponse($operation);
        $response->content = [];

        $response->setContent(
            self::JSON_API_MEDIA_TYPE,
            Schema::fromType(
                (new OpenApiObjectType)
                    ->addProperty('data', (new OpenApiArrayType)->setItems(
                        $this->openApiTransformer->transform(new ObjectType($resourceClass)),
                    ))
                    ->setRequired(['data']),
            ),
        );
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

    private function addJsonApiQueryParameters(Operation $operation): void
    {
        $existing = collect($operation->parameters)
            ->filter(fn (mixed $parameter): bool => $parameter instanceof Parameter && $parameter->in === 'query')
            ->map(fn (Parameter $parameter): string => $parameter->name)
            ->all();

        $parameters = collect($this->jsonApiQueryParameters())
            ->reject(fn (Parameter $parameter): bool => in_array($parameter->name, $existing, true))
            ->values()
            ->all();

        $operation->addParameters($parameters);
    }

    /**
     * @return list<Parameter>
     */
    private function jsonApiQueryParameters(): array
    {
        return [
            Parameter::make('filter', 'query')
                ->setStyle('deepObject')
                ->setExplode(true)
                ->setSchema(Schema::fromType(new OpenApiObjectType))
                ->description('JSON:API filters. Use filter[field]=value, operator objects like filter[field][gte]=10, relationship filters, or configured custom filters.'),

            Parameter::make('sort', 'query')
                ->setSchema(Schema::fromType(new StringType))
                ->description('Comma-separated JSON:API sort fields. Prefix a field with - for descending order.'),

            Parameter::make('include', 'query')
                ->setSchema(Schema::fromType(new StringType))
                ->description('Comma-separated JSON:API relationship include paths.'),

            Parameter::make('includeFilter', 'query')
                ->setStyle('deepObject')
                ->setExplode(true)
                ->setSchema(Schema::fromType(new OpenApiObjectType))
                ->description('Filters for included relationships. Include filters must target requested relationship.attribute paths.'),

            Parameter::make('fields', 'query')
                ->setStyle('deepObject')
                ->setExplode(true)
                ->setSchema(Schema::fromType(new OpenApiObjectType))
                ->description('JSON:API sparse fieldsets by resource type.'),

            Parameter::make('page[number]', 'query')
                ->setSchema(Schema::fromType((new IntegerType)->setMin(1)))
                ->description('Page number.'),

            Parameter::make('page[size]', 'query')
                ->setSchema(Schema::fromType(new IntegerType))
                ->description('Page size. Use -1 only for resources that explicitly allow unpaginated collections.'),
        ];
    }
}
