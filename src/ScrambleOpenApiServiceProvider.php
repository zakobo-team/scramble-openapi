<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi;

use Dedoc\Scramble\Scramble;
use Illuminate\Support\ServiceProvider;

class ScrambleOpenApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/scramble-openapi.php', 'scramble-openapi');

        if (is_array(config('scramble-sso-auth-driver'))) {
            config([
                'scramble-openapi' => array_replace_recursive(
                    config('scramble-openapi', []),
                    config('scramble-sso-auth-driver', []),
                ),
            ]);
        }
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'scramble-openapi');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'scramble-sso-auth-driver');

        if ((bool) config('scramble-openapi.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }

        if ((bool) config('scramble-openapi.enabled', true)
            && (bool) config('scramble-openapi.scramble.auto_configure', true)) {
            $configuration = Scramble::configure();

            if ((bool) config('scramble-openapi.scramble.prefer_patch_method', true)) {
                $configuration->preferPatchMethod();
            }

            ScrambleOpenApi::configure($configuration);
        }

        $this->publishes([
            __DIR__.'/../config/scramble-openapi.php' => config_path('scramble-openapi.php'),
        ], 'scramble-openapi-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/scramble-openapi'),
        ], 'scramble-openapi-views');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/scramble-sso-auth-driver'),
        ], 'scramble-sso-auth-driver-views');

        $this->publishes([
            __DIR__.'/../config/scramble-openapi.php' => config_path('scramble-sso-auth-driver.php'),
        ], 'scramble-sso-auth-driver-config');
    }
}
