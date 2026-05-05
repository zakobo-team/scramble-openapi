<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi;

use Dedoc\Scramble\Generator;
use Dedoc\Scramble\Scramble;
use Illuminate\Contracts\View\Factory;
use Zakobo\ScrambleOpenApi\OpenApi\DocumentUuidRouteParameters;
use Zakobo\ScrambleOpenApi\OpenApi\JsonApiCollectionOperationTransformer;
use Zakobo\ScrambleOpenApi\OpenApi\JsonApiErrorResponses;
use Zakobo\ScrambleOpenApi\OpenApi\RemoveRecursiveIncludeEnumValues;
use Zakobo\ScrambleOpenApi\OpenApi\ScrambleAuthentication;
use Zakobo\ScrambleOpenApi\OpenApi\ScrambleSecurityRequirements;

class ScrambleOpenApi
{
    public static function configure(object $configuration): object
    {
        return $configuration
            ->expose(ui: function ($router, $action) {
                return $router->get(config('scramble-openapi.scramble.ui_path', '/docs/api'), function (Generator $generator) {
                    $config = Scramble::getGeneratorConfig(Scramble::DEFAULT_API);

                    return app(Factory::class)->make('scramble-openapi::scramble-docs', [
                        'spec' => $generator($config),
                        'config' => $config,
                        'tenantEnabled' => config('scramble-openapi.tenant.enabled', false),
                        'tenantId' => config('scramble-openapi.tenant.id', 'swagger'),
                        'tenantHeaderName' => config('scramble-openapi.tenant.header_name', 'X-Tenant-ID'),
                    ]);
                });
            }, document: config('scramble-openapi.scramble.document_path', '/docs/api.json'))
            ->withOperationTransformers([
                ScrambleSecurityRequirements::class,
                JsonApiCollectionOperationTransformer::class,
            ])
            ->withDocumentTransformers([
                ScrambleAuthentication::class,
                JsonApiErrorResponses::class,
                DocumentUuidRouteParameters::class,
                RemoveRecursiveIncludeEnumValues::class,
            ]);
    }
}
