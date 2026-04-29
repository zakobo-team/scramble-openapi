<?php

declare(strict_types=1);

namespace Zakobo\ScrambleSsoAuthDriver\OpenApi;

use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityRequirement;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\Generator\SecuritySchemes\OAuthFlow;

class ScrambleAuthentication
{
    public function __invoke(OpenApi $openApi): void
    {
        $oauthScheme = (string) config('scramble-sso-auth-driver.swagger_ui.oauth_scheme', 'oauth2');

        $openApi->components->securitySchemes[$oauthScheme] = SecurityScheme::oauth2()
            ->flow('authorizationCode', function (OAuthFlow $flow): void {
                $flow
                    ->authorizationUrl((string) config('scramble-sso-auth-driver.oauth2.authorization_url'))
                    ->tokenUrl((string) config('scramble-sso-auth-driver.oauth2.token_url'));
            });

        $security = [$oauthScheme => []];

        if ((bool) config('scramble-sso-auth-driver.tenant.enabled', false)) {
            $tenantScheme = (string) config('scramble-sso-auth-driver.tenant.scheme', 'tenantHeader');
            $tenantHeaderName = (string) config('scramble-sso-auth-driver.tenant.header_name', 'X-Tenant-ID');

            $openApi->components->securitySchemes[$tenantScheme] = SecurityScheme::apiKey('header', $tenantHeaderName)
                ->setDescription('Tenant identifier required for tenant API requests.');

            $security[$tenantScheme] = [];
        }

        $openApi->security[] = new SecurityRequirement($security);
    }
}
