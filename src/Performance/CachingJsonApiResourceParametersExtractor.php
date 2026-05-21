<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Performance;

use Dedoc\Scramble\Support\OperationExtensions\ParameterExtractor\JsonApiResourceParametersExtractor;
use Dedoc\Scramble\Support\OperationExtensions\ParameterExtractor\ParameterExtractor;
use Dedoc\Scramble\Support\OperationExtensions\RulesExtractor\ParametersExtractionResult;
use Dedoc\Scramble\Support\RouteInfo;
use ReflectionMethod;

/**
 * Decorates Scramble's JsonApiResourceParametersExtractor with a per-resource-class cache.
 *
 * The extracted parameters (include + sparse fieldsets) depend only on the JSON:API
 * resource class returned by the controller action, so we memoize the result by class
 * name across routes. With many routes sharing a small set of resource classes this
 * collapses extraction work dramatically (in benchmarks: ~270 routes × ~100 ms each
 * → a handful of computations).
 *
 * The cached results are deep-cloned on retrieval because downstream document
 * transformers (e.g. RemoveRecursiveIncludeEnumValues) mutate Parameter objects
 * in place; without cloning, those mutations would leak across operations.
 */
class CachingJsonApiResourceParametersExtractor implements ParameterExtractor
{
    /** @var array<string, list<ParametersExtractionResult>> */
    private static array $resultsByResourceClass = [];

    private readonly ReflectionMethod $getResourceTypeReflection;

    public function __construct(private readonly JsonApiResourceParametersExtractor $inner)
    {
        $this->getResourceTypeReflection = new ReflectionMethod($this->inner, 'getResourceType');
    }

    public function handle(RouteInfo $routeInfo, array $parameterExtractionResults): array
    {
        $returnType = $routeInfo->getActionType()?->getReturnType();

        if (! $returnType) {
            return $parameterExtractionResults;
        }

        $resourceType = $this->getResourceTypeReflection->invoke($this->inner, $returnType);

        if (! $resourceType) {
            return $parameterExtractionResults;
        }

        $cacheKey = $resourceType->name;

        if (! isset(self::$resultsByResourceClass[$cacheKey])) {
            self::$resultsByResourceClass[$cacheKey] = $this->inner->handle($routeInfo, []);
        }

        return [
            ...$parameterExtractionResults,
            ...$this->cloneResults(self::$resultsByResourceClass[$cacheKey]),
        ];
    }

    public static function clearCache(): void
    {
        self::$resultsByResourceClass = [];
    }

    /**
     * @param  list<ParametersExtractionResult>  $results
     * @return list<ParametersExtractionResult>
     */
    private function cloneResults(array $results): array
    {
        return array_map(
            fn (ParametersExtractionResult $result): ParametersExtractionResult => unserialize(serialize($result)),
            $results,
        );
    }
}
