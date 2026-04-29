<?php

declare(strict_types=1);

namespace Zakobo\ScrambleSsoAuthDriver\Http\Controllers;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;

class SwaggerUiController
{
    public function __invoke(Factory $view): View
    {
        $oauthRedirectUrl = config('scramble-sso-auth-driver.oauth2.redirect_url')
            ?: url((string) config('scramble-sso-auth-driver.swagger_ui.oauth_redirect_path', '/oauth2-redirect.html'));
        $authBootstrapPath = config('scramble-sso-auth-driver.swagger_ui.auth_bootstrap_path');

        return $view->make('scramble-sso-auth-driver::swagger-ui', [
            'specUrl' => url((string) config('scramble-sso-auth-driver.swagger_ui.spec_path', '/docs/api.json')),
            'authBootstrapUrl' => filled($authBootstrapPath) ? url((string) $authBootstrapPath) : null,
            'oauth2RedirectUrl' => $oauthRedirectUrl,
            'oauthScopes' => config('scramble-sso-auth-driver.oauth2.scopes', []),
            'oauthMetadata' => [
                'client_id' => config('scramble-sso-auth-driver.oauth2.client_id'),
                'authorization_url' => config('scramble-sso-auth-driver.oauth2.authorization_url'),
                'token_url' => config('scramble-sso-auth-driver.oauth2.token_url'),
            ],
            'oauthScheme' => config('scramble-sso-auth-driver.swagger_ui.oauth_scheme', 'oauth2'),
            'tenantEnabled' => config('scramble-sso-auth-driver.tenant.enabled', false),
            'tenantId' => config('scramble-sso-auth-driver.tenant.id', 'swagger'),
            'tenantScheme' => config('scramble-sso-auth-driver.tenant.scheme', 'tenantHeader'),
            'tenantHeaderName' => config('scramble-sso-auth-driver.tenant.header_name', 'X-Tenant-ID'),
            'oauthTenantParameter' => config('scramble-sso-auth-driver.tenant.oauth_parameter', 'tenant_id'),
            'swaggerUiDistVersion' => config('scramble-sso-auth-driver.swagger_ui.swagger_ui_dist_version', '5.20.1'),
        ]);
    }
}
