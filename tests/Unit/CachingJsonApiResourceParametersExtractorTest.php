<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Unit;

use Dedoc\Scramble\Enums\JsonApiArraySerialization;
use Dedoc\Scramble\GeneratorConfig;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\ContainerUtils;
use Dedoc\Scramble\Support\Factories\JsonApiQueryParameterFactory;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\OperationExtensions\ParameterExtractor\JsonApiResourceParametersExtractor;
use Dedoc\Scramble\Support\OperationExtensions\ParameterExtractor\ParameterExtractor;
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

    #[Test]
    public function it_does_not_share_parameters_with_an_endpoint_that_ignores_the_query_string(): void
    {
        $config = Scramble::getGeneratorConfig(Scramble::DEFAULT_API);

        $withQueryString = $this->extractor($config)->handle($this->routeInfo('api/v1/first'), []);
        $withoutQueryString = $this->extractor($config)->handle($this->routeInfo('api/v1/second', 'showWithoutQueryString'), []);

        $this->assertSame('fields[example_json_apis]', $withQueryString[0]->parameters[0]->name);
        $this->assertSame([], $withoutQueryString);
    }

    #[Test]
    public function it_does_not_share_parameters_between_array_serializations(): void
    {
        $commaConfig = Scramble::getGeneratorConfig(Scramble::DEFAULT_API)->cloneWithoutExposing('comma');
        $stringConfig = $commaConfig->cloneWithoutExposing('string')->jsonApi(JsonApiArraySerialization::String);

        $comma = $this->extractor($commaConfig)->handle($this->routeInfo('api/v1/first'), []);
        $string = $this->extractor($stringConfig)->handle($this->routeInfo('api/v1/first'), []);

        $this->assertInstanceOf(ArrayType::class, $comma[0]->parameters[0]->schema->type);
        $this->assertInstanceOf(StringType::class, $string[0]->parameters[0]->schema->type);
    }

    private function extractor(GeneratorConfig $config): ParameterExtractor
    {
        return ContainerUtils::makeContextable(JsonApiResourceParametersExtractor::class, [
            GeneratorConfig::class => $config,
            JsonApiQueryParameterFactory::class => new JsonApiQueryParameterFactory($config->jsonApi->arraySerialization),
        ]);
    }

    private function routeInfo(string $uri, string $action = 'show'): RouteInfo
    {
        return new RouteInfo(new Route(['GET'], $uri, [JsonApiResourceController::class, $action]), 'GET');
    }
}
