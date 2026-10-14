<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Performance;

use Dedoc\Scramble\Support\OperationExtensions\ParameterExtractor\JsonApiResourceParametersExtractor;
use Dedoc\Scramble\Support\OperationExtensions\ParameterExtractor\ParameterExtractor;
use Dedoc\Scramble\Support\OperationExtensions\RulesExtractor\ParametersExtractionResult;
use Dedoc\Scramble\Support\RouteInfo;
use ReflectionMethod;

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
