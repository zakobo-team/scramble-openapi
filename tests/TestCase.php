<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests;

use Dedoc\Scramble\ScrambleServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Zakobo\ScrambleOpenApi\ScrambleOpenApiServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ScrambleServiceProvider::class,
            ScrambleOpenApiServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }
}
