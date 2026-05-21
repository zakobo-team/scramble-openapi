<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Unit;

use Dedoc\Scramble\Generator;
use Dedoc\Scramble\Support\OperationExtensions\ParameterExtractor\JsonApiResourceParametersExtractor;
use PHPUnit\Framework\Attributes\Test;
use Zakobo\ScrambleOpenApi\Performance\CachedScrambleGenerator;
use Zakobo\ScrambleOpenApi\Performance\CachingJsonApiResourceParametersExtractor;
use Zakobo\ScrambleOpenApi\Tests\TestCase;

class PerformanceServiceProviderBindingsTest extends TestCase
{
    #[Test]
    public function it_decorates_the_json_api_resource_parameters_extractor(): void
    {
        $container = $this->app;

        $extendersReflection = new \ReflectionProperty($container, 'extenders');
        /** @var array<string, list<\Closure>> $extenders */
        $extenders = $extendersReflection->getValue($container);

        $this->assertArrayHasKey(JsonApiResourceParametersExtractor::class, $extenders);
        $this->assertNotEmpty($extenders[JsonApiResourceParametersExtractor::class]);

        $innerStub = new class extends JsonApiResourceParametersExtractor
        {
            public function __construct() {}
        };

        $wrapped = ($extenders[JsonApiResourceParametersExtractor::class][0])($innerStub, $container);

        $this->assertInstanceOf(CachingJsonApiResourceParametersExtractor::class, $wrapped);
    }

    #[Test]
    public function it_does_not_decorate_the_generator_when_cache_is_disabled(): void
    {
        $this->assertFalse((bool) config('scramble-openapi.cache.enabled'));

        $generator = $this->app->make(Generator::class);

        $this->assertNotInstanceOf(CachedScrambleGenerator::class, $generator);
    }

    #[Test]
    public function it_decorates_the_generator_when_cache_is_enabled(): void
    {
        putenv('SCRAMBLE_OPENAPI_CACHE_ENABLED=true');

        $this->refreshApplication();

        try {
            $generator = $this->app->make(Generator::class);

            $this->assertInstanceOf(CachedScrambleGenerator::class, $generator);
        } finally {
            putenv('SCRAMBLE_OPENAPI_CACHE_ENABLED');
        }
    }
}
