<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\OpenApi;

use Dedoc\Scramble\Contracts\OperationTransformer;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\SecurityRequirement;
use Dedoc\Scramble\Support\RouteInfo;
use Illuminate\Support\Str;

class ScrambleSecurityRequirements implements OperationTransformer
{
    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        if ($operation->security === []) {
            return;
        }

        $uri = $routeInfo->route->uri;
        $apiPrefix = (string) config('scramble-openapi.security.api_prefix', 'api/v4/');

        if (! Str::startsWith($uri, $apiPrefix)) {
            return;
        }

        $oauthScheme = (string) config('scramble-openapi.swagger_ui.oauth_scheme', 'oauth2');
        $tenantEnabled = (bool) config('scramble-openapi.tenant.enabled', false);
        $tenantOnlyUriPatterns = config('scramble-openapi.security.tenant_only_uri_patterns', []);

        if (! is_array($tenantOnlyUriPatterns)) {
            $tenantOnlyUriPatterns = [];
        }

        if ($tenantEnabled) {
            $tenantScheme = (string) config('scramble-openapi.tenant.scheme', 'tenantHeader');

            foreach ($tenantOnlyUriPatterns as $pattern) {
                if (is_string($pattern) && Str::is($pattern, $uri)) {
                    $operation->security = [
                        new SecurityRequirement([$tenantScheme => []]),
                    ];

                    return;
                }
            }
        }

        $security = [$oauthScheme => []];

        if ($tenantEnabled) {
            $security[(string) config('scramble-openapi.tenant.scheme', 'tenantHeader')] = [];
        }

        $operation->security = [
            new SecurityRequirement($security),
        ];
    }
}
