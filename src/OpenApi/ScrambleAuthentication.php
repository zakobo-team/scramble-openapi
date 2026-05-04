<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\OpenApi;

use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityRequirement;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\Generator\SecuritySchemes\OAuthFlow;

class ScrambleAuthentication
{
    public function __invoke(OpenApi $openApi): void
    {
        $oauthScheme = (string) config('scramble-openapi.swagger_ui.oauth_scheme', 'oauth2');

        $openApi->components->securitySchemes[$oauthScheme] = SecurityScheme::oauth2()
            ->flow('authorizationCode', function (OAuthFlow $flow): void {
                $flow
                    ->authorizationUrl((string) config('scramble-openapi.oauth2.authorization_url'))
                    ->tokenUrl((string) config('scramble-openapi.oauth2.token_url'));
            });

        $security = [$oauthScheme => []];

        if ((bool) config('scramble-openapi.tenant.enabled', false)) {
            $tenantScheme = (string) config('scramble-openapi.tenant.scheme', 'tenantHeader');
            $tenantHeaderName = (string) config('scramble-openapi.tenant.header_name', 'X-Tenant-ID');

            $openApi->components->securitySchemes[$tenantScheme] = SecurityScheme::apiKey('header', $tenantHeaderName)
                ->setDescription('Tenant identifier required for tenant API requests.');

            $security[$tenantScheme] = [];
        }

        $openApi->security[] = new SecurityRequirement($security);
    }
}
