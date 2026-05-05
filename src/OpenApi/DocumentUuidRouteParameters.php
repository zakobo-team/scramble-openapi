<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\OpenApi;

use Dedoc\Scramble\Contracts\DocumentTransformer;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

class DocumentUuidRouteParameters implements DocumentTransformer
{
    public function handle(OpenApi $document, OpenApiContext $context): void
    {
        $routeParameters = $this->uuidRouteParametersByPathAndMethod();

        foreach ($document->paths as $path) {
            foreach ($path->operations as $operation) {
                $names = $routeParameters[$this->normalizePath($path->path)][$operation->method] ?? [];

                foreach ($operation->parameters as $parameter) {
                    if ($this->shouldDocumentAsUuid($parameter, $names)) {
                        $parameter->setSchema(Schema::fromType((new StringType)->format('uuid')));
                    }
                }
            }
        }
    }

    /**
     * @return array<string, array<string, list<string>>>
     */
    private function uuidRouteParametersByPathAndMethod(): array
    {
        $parameters = [];

        foreach (RouteFacade::getRoutes()->getRoutes() as $route) {
            $parameterNames = $this->uuidRouteParameterNamesFor($route);

            foreach ($this->documentedPathsFor($route, $parameterNames) as $path => $names) {
                foreach ($route->methods() as $method) {
                    $parameters[$path][strtolower($method)] = $names;
                }
            }
        }

        return $parameters;
    }

    /**
     * @return array<string, string>
     */
    private function uuidRouteParameterNamesFor(Route $route): array
    {
        $modelParameters = $this->uuidModelParameterNames($route);

        return collect($route->parameterNames())
            ->mapWithKeys(function (string $name) use ($modelParameters): array {
                $documentedName = collect($modelParameters)->first(fn (string $modelName): bool => $modelName === $name
                    || $modelName === Str::camel($name));

                return is_string($documentedName) ? [$name => $documentedName] : [];
            })
            ->all();
    }

    /**
     * @param  array<string, string>  $parameterNames
     * @return array<string, list<string>>
     */
    private function documentedPathsFor(Route $route, array $parameterNames): array
    {
        if ($parameterNames === []) {
            return [];
        }

        return [
            $this->normalizePath($route->uri()) => array_keys($parameterNames),
            $this->pathWithDocumentedParameterNames($route, $parameterNames) => array_values($parameterNames),
        ];
    }

    /**
     * @param  array<string, string>  $parameterNames
     */
    private function pathWithDocumentedParameterNames(Route $route, array $parameterNames): string
    {
        return collect($parameterNames)
            ->reduce(
                fn (string $path, string $documented, string $routeName): string => str_replace(
                    '{'.$routeName.'}',
                    '{'.$documented.'}',
                    $path,
                ),
                $this->normalizePath($route->uri()),
            );
    }

    /**
     * @return list<string>
     */
    private function uuidModelParameterNames(Route $route): array
    {
        $method = $this->actionMethod($route);

        if (! $method instanceof ReflectionMethod) {
            return [];
        }

        return collect($method->getParameters())
            ->filter(fn (ReflectionParameter $parameter): bool => $this->isUuidRouteModel($parameter))
            ->map(fn (ReflectionParameter $parameter): string => $parameter->getName())
            ->values()
            ->all();
    }

    private function actionMethod(Route $route): ?ReflectionMethod
    {
        $action = $route->getActionName();

        if ($action === 'Closure') {
            return null;
        }

        [$class, $method] = str_contains($action, '@')
            ? explode('@', $action, 2)
            : [$action, '__invoke'];

        return class_exists($class) && method_exists($class, $method)
            ? new ReflectionMethod($class, $method)
            : null;
    }

    private function isUuidRouteModel(ReflectionParameter $parameter): bool
    {
        $type = $parameter->getType();

        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return false;
        }

        $class = $type->getName();

        return is_subclass_of($class, Model::class)
            && (new $class)->getRouteKeyName() === 'uuid';
    }

    /**
     * @param  list<string>  $uuidParameterNames
     */
    private function shouldDocumentAsUuid(mixed $parameter, array $uuidParameterNames): bool
    {
        return $parameter instanceof Parameter
            && $parameter->in === 'path'
            && in_array($parameter->name, $uuidParameterNames, true);
    }

    private function normalizePath(string $path): string
    {
        $normalized = trim($path, '/');

        return str_starts_with($normalized, 'api/')
            ? substr($normalized, 4)
            : $normalized;
    }
}
