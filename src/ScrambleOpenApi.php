<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi;

use Dedoc\Scramble\CacheableGenerator;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\DevTools;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Zakobo\ScrambleOpenApi\OpenApi\DocumentFilterEnumSchemas;
use Zakobo\ScrambleOpenApi\OpenApi\DocumentUuidRouteParameters;
use Zakobo\ScrambleOpenApi\OpenApi\JsonApiErrorResponses;
use Zakobo\ScrambleOpenApi\OpenApi\RemoveRecursiveIncludeEnumValues;
use Zakobo\ScrambleOpenApi\OpenApi\ScrambleAuthentication;
use Zakobo\ScrambleOpenApi\OpenApi\ScrambleSecurityRequirements;

class ScrambleOpenApi
{
    public static function configure(object $configuration): object
    {
        return $configuration
            ->expose(
                ui: function (Router $router): Route {
                    return $router->get('/docs/api', function (): View|Response {
                        $config = Scramble::getGeneratorConfig(Scramble::DEFAULT_API);

                        if (DevTools::enabled()) {
                            $result = app(CacheableGenerator::class)->generate($config);

                            return view($config->renderer()->view, [
                                'spec' => $result->spec(),
                                'config' => $config,
                                'result' => $result,
                            ]);
                        }

                        $documentPath = public_path('docs/api.json');

                        if (! is_file($documentPath) || ! is_readable($documentPath)) {
                            return response(
                                'Exported OpenAPI document not found. Run php artisan scramble:export --path=public/docs/api.json.',
                                404,
                            );
                        }

                        $spec = file_get_contents($documentPath);

                        if ($spec === false) {
                            return response('Exported OpenAPI document could not be read.', 500);
                        }

                        return view($config->renderer()->view, [
                            'spec' => json_decode($spec, associative: true),
                            'config' => $config,
                        ]);
                    });
                },
                document: '/docs/api.json',
            )
            ->withOperationTransformers([
                ScrambleSecurityRequirements::class,
            ])
            ->withDocumentTransformers([
                ScrambleAuthentication::class,
                JsonApiErrorResponses::class,
                DocumentUuidRouteParameters::class,
                RemoveRecursiveIncludeEnumValues::class,
                DocumentFilterEnumSchemas::class,
            ]);
    }
}
