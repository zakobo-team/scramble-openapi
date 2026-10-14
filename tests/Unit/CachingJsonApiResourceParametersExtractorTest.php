<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Unit;

use Dedoc\Scramble\Enums\JsonApiArraySerialization;
use Dedoc\Scramble\GeneratorConfig;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\ContainerUtils;
use Dedoc\Scramble\Support\Factories\JsonApiQueryParameterFactory;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\OperationExtensions\ParameterExtractor\JsonApiResourceParametersExtractor;
use Dedoc\Scramble\Support\OperationExtensions\RulesExtractor\ParametersExtractionResult;
use Dedoc\Scramble\Support\RouteInfo;
use Dedoc\Scramble\Support\TypeManagers\JsonApiResourceTypeManager;
use Illuminate\Routing\Route;
use PHPUnit\Framework\Attributes\Test;
use Zakobo\ScrambleOpenApi\Performance\CachingJsonApiResourceParametersExtractor;
use Zakobo\ScrambleOpenApi\Tests\Fixtures\JsonApiResourceController;
use Zakobo\ScrambleOpenApi\Tests\TestCase;

class CachingJsonApiResourceParametersExtractorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        CachingJsonApiResourceParametersExtractor::clearCache();
    }

    #[Test]
    public function it_extracts_once_per_resource_class_and_returns_independent_copies(): void
    {
        $inner = new class extends JsonApiResourceParametersExtractor
        {
            public int $handleCalls = 0;

            public function __construct()
            {
                parent::__construct(
                    Scramble::getGeneratorConfig(Scramble::DEFAULT_API),
                    new JsonApiQueryParameterFactory(JsonApiArraySerialization::Comma),
                    new JsonApiResourceTypeManager,
                );
            }

            public function handle(RouteInfo $routeInfo, array $parameterExtractionResults): array
            {
                $this->handleCalls++;

                return [new ParametersExtractionResult([Parameter::make('include', 'query')])];
            }
        };
        $extractor = new CachingJsonApiResourceParametersExtractor($inner);

        $first = $extractor->handle($this->routeInfo('api/v1/first'), []);
        $second = $extractor->handle($this->routeInfo('api/v1/second'), []);

        $this->assertSame(1, $inner->handleCalls);
        $this->assertEquals($first, $second);
        $this->assertNotSame($first[0]->parameters[0], $second[0]->parameters[0]);

        $first[0]->parameters[0]->description('Changed by a document transformer.');

        $this->assertSame('', $second[0]->parameters[0]->description);
    }

    #[Test]
    public function it_decorates_the_json_api_resource_parameters_extractor_in_the_container(): void
    {
        $extractor = ContainerUtils::makeContextable(JsonApiResourceParametersExtractor::class, [
            GeneratorConfig::class => Scramble::getGeneratorConfig(Scramble::DEFAULT_API),
            JsonApiQueryParameterFactory::class => new JsonApiQueryParameterFactory(JsonApiArraySerialization::Comma),
        ]);

        $this->assertInstanceOf(CachingJsonApiResourceParametersExtractor::class, $extractor);
    }

    private function routeInfo(string $uri): RouteInfo
    {
        return new RouteInfo(new Route(['GET'], $uri, [JsonApiResourceController::class, 'show']), 'GET');
    }
}
