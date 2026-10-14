<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\OperationExtensions\ParameterExtractor\JsonApiResourceParametersExtractor;
use Illuminate\Support\ServiceProvider;
use Zakobo\ScrambleOpenApi\OpenApi\ScrambleAuthentication;
use Zakobo\ScrambleOpenApi\Performance\CachingJsonApiResourceParametersExtractor;

class ScrambleOpenApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/scramble-openapi.php', 'scramble-openapi');

        config(['scramble.renderers.scalar.authentication' => ScrambleAuthentication::scalarAuthentication()]);

        $this->app->extend(
            JsonApiResourceParametersExtractor::class,
            fn (JsonApiResourceParametersExtractor $inner): CachingJsonApiResourceParametersExtractor => new CachingJsonApiResourceParametersExtractor($inner),
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        ScrambleOpenApi::configure(Scramble::configure()->preferPatchMethod());

        $this->publishes([
            __DIR__.'/../config/scramble-openapi.php' => config_path('scramble-openapi.php'),
        ], 'scramble-openapi-config');
    }
}
