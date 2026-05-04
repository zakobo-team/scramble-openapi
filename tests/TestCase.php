<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests;

use Dedoc\Scramble\ScrambleServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Zakobo\JsonApiQuery\JsonApiQueryServiceProvider;
use Zakobo\ScrambleOpenApi\ScrambleOpenApiServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ScrambleServiceProvider::class,
            JsonApiQueryServiceProvider::class,
            ScrambleOpenApiServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
