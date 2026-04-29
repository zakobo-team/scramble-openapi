<?php

declare(strict_types=1);

namespace Zakobo\ScrambleSsoAuthDriver\Tests;

use Dedoc\Scramble\ScrambleServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Zakobo\ScrambleSsoAuthDriver\ScrambleSsoAuthDriverServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ScrambleServiceProvider::class,
            ScrambleSsoAuthDriverServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }
}
