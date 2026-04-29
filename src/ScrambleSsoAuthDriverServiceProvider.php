<?php

declare(strict_types=1);

namespace Zakobo\ScrambleSsoAuthDriver;

use Dedoc\Scramble\Scramble;
use Illuminate\Support\ServiceProvider;

class ScrambleSsoAuthDriverServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/scramble-sso-auth-driver.php', 'scramble-sso-auth-driver');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'scramble-sso-auth-driver');

        if ((bool) config('scramble-sso-auth-driver.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }

        if ((bool) config('scramble-sso-auth-driver.enabled', true)
            && (bool) config('scramble-sso-auth-driver.scramble.auto_configure', true)) {
            $configuration = Scramble::configure();

            if ((bool) config('scramble-sso-auth-driver.scramble.prefer_patch_method', true)) {
                $configuration->preferPatchMethod();
            }

            ScrambleSsoAuthDriver::configure($configuration);
        }

        $this->publishes([
            __DIR__.'/../config/scramble-sso-auth-driver.php' => config_path('scramble-sso-auth-driver.php'),
        ], 'scramble-sso-auth-driver-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/scramble-sso-auth-driver'),
        ], 'scramble-sso-auth-driver-views');
    }
}
