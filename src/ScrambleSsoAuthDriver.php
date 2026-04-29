<?php

declare(strict_types=1);

namespace Zakobo\ScrambleSsoAuthDriver;

use Dedoc\Scramble\Generator;
use Dedoc\Scramble\Scramble;
use Illuminate\Contracts\View\Factory;
use Zakobo\ScrambleSsoAuthDriver\OpenApi\ScrambleAuthentication;
use Zakobo\ScrambleSsoAuthDriver\OpenApi\ScrambleSecurityRequirements;

class ScrambleSsoAuthDriver
{
    public static function configure(object $configuration): object
    {
        return $configuration
            ->expose(ui: function ($router, $action) {
                return $router->get(config('scramble-sso-auth-driver.scramble.ui_path', '/docs/api'), function (Generator $generator) {
                    $config = Scramble::getGeneratorConfig(Scramble::DEFAULT_API);

                    return app(Factory::class)->make('scramble-sso-auth-driver::scramble-docs', [
                        'spec' => $generator($config),
                        'config' => $config,
                        'tenantEnabled' => config('scramble-sso-auth-driver.tenant.enabled', false),
                        'tenantId' => config('scramble-sso-auth-driver.tenant.id', 'swagger'),
                        'tenantHeaderName' => config('scramble-sso-auth-driver.tenant.header_name', 'X-Tenant-ID'),
                    ]);
                });
            }, document: config('scramble-sso-auth-driver.scramble.document_path', '/docs/api.json'))
            ->withOperationTransformers(ScrambleSecurityRequirements::class)
            ->withDocumentTransformers(ScrambleAuthentication::class);
    }
}
