<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Zakobo\ScrambleOpenApi\Tests\TestCase;

class SwaggerUiTest extends TestCase
{
    #[Test]
    public function it_serves_the_swagger_ui_with_auth_bootstrap_configuration(): void
    {
        config(['scramble-openapi.swagger_ui.auth_bootstrap_path' => '/api/v4/pa/auth-bootstrap']);

        $response = $this->get('/docs/swagger');

        $response->assertOk();
        $response->assertViewHas('specUrl', url('/docs/api.json'));
        $response->assertViewHas('authBootstrapUrl', url('/api/v4/pa/auth-bootstrap'));
        $response->assertViewHas('oauth2RedirectUrl', url('/oauth2-redirect.html'));
        $response->assertViewHas('oauthMetadata', [
            'client_id' => null,
            'authorization_url' => 'https://auth.zakobo.test/oauth/authorize',
            'token_url' => 'https://auth.zakobo.test/oauth/token',
        ]);
        $response->assertViewHas('oauthScheme', 'oauth2');
        $response->assertViewHas('tenantEnabled', false);
        $response->assertSee('usePkceWithAuthorizationCodeGrant', false);
        $response->assertSee('useBasicAuthenticationWithAccessCodeGrant: false', false);
        $response->assertSee('config.tenantEnabled && config.oauthTenantParameter', false);
        $response->assertSee('spec.components?.securitySchemes?.[config.oauthScheme]', false);
        $response->assertSee('authorizationUrl = oauth.authorization_url', false);
        $response->assertSee('tokenUrl = oauth.token_url', false);
        $response->assertSee('resolveOAuthMetadata(config, headers)', false);
    }

    #[Test]
    public function it_serves_a_zakobo_endpoint_filter_that_matches_operation_metadata(): void
    {
        $response = $this->get('/docs/swagger');

        $response->assertOk();
        $response->assertSee("endpointFilterId: 'swagger-endpoint-filter'", false);
        $response->assertSee('ensureEndpointFilterInput(config)', false);
        $response->assertSee("content.className = 'wrapper'", false);
        $response->assertSee('schemeContainer.parentElement.insertBefore(toolbar, schemeContainer.nextSibling)', false);
        $response->assertSee('Filter by method, path, summary or tag. Example: cms, /v4/pa, products', false);
        $response->assertSee('bootEndpointFilter(config)', false);
        $response->assertSee('opblock-summary-path', false);
        $response->assertSee('opblock-summary-method', false);
        $response->assertSee('opblock-summary-description', false);
    }

    #[Test]
    public function it_can_use_static_oauth_metadata_without_auth_bootstrap(): void
    {
        config(['scramble-openapi.oauth2.client_id' => 'docs-client-id']);

        $response = $this->get('/docs/swagger');

        $response->assertOk();
        $response->assertViewHas('authBootstrapUrl', null);
        $response->assertViewHas('oauthMetadata', [
            'client_id' => 'docs-client-id',
            'authorization_url' => 'https://auth.zakobo.test/oauth/authorize',
            'token_url' => 'https://auth.zakobo.test/oauth/token',
        ]);
        $response->assertSee('Missing OAuth client_id. Configure auth_bootstrap_path or oauth2.client_id.', false);
    }

    #[Test]
    public function it_serves_the_oauth2_redirect_page_on_the_configured_swagger_callback_path(): void
    {
        $response = $this->get('/oauth2-redirect.html');

        $response->assertOk();
        $response->assertSee('swaggerUIRedirectOauth2', false);
    }

    #[Test]
    public function it_serves_the_legacy_oauth2_redirect_page(): void
    {
        $response = $this->get('/docs/swagger/oauth2-redirect');

        $response->assertOk();
        $response->assertSee('swaggerUIRedirectOauth2', false);
    }

    #[Test]
    public function it_keeps_legacy_route_names_available(): void
    {
        $this->assertSame(url('/docs/swagger'), route('scramble-sso-auth-driver.swagger-ui'));
        $this->assertSame(url('/oauth2-redirect.html'), route('scramble-sso-auth-driver.oauth2-redirect'));
        $this->assertSame(url('/docs/swagger/oauth2-redirect'), route('scramble-sso-auth-driver.oauth2-redirect.legacy'));
    }

    #[Test]
    public function it_replaces_the_default_swagger_authorize_modal_with_direct_oauth_actions(): void
    {
        $response = $this->get('/docs/swagger');

        $response->assertOk();
        $response->assertSee('id="swagger-auth-button"', false);
        $response->assertSee('.swagger-ui .scheme-container .auth-wrapper', false);
        $response->assertSee('authPopup', false);
        $response->assertSee('authorizeAccessCodeWithFormParams', false);
        $response->assertSee('logoutWithPersistOption', false);
        $response->assertSee("button.textContent = isAuthorized(config) ? 'Logout' : 'Authentication'", false);
    }

    #[Test]
    public function it_uses_configured_oauth_redirect_url_when_provided(): void
    {
        config(['scramble-openapi.oauth2.redirect_url' => 'https://api.example.test/oauth2-redirect.html']);

        $response = $this->get('/docs/swagger');

        $response->assertOk();
        $response->assertViewHas('oauth2RedirectUrl', 'https://api.example.test/oauth2-redirect.html');
    }

    #[Test]
    public function it_passes_custom_tenant_security_schemes_and_scopes_to_the_swagger_ui(): void
    {
        config([
            'scramble-openapi.swagger_ui.oauth_scheme' => 'sso',
            'scramble-openapi.swagger_ui.swagger_ui_dist_version' => '5.20.1',
            'scramble-openapi.tenant.enabled' => true,
            'scramble-openapi.tenant.id' => 'accounting-swagger',
            'scramble-openapi.tenant.scheme' => 'tenant',
            'scramble-openapi.tenant.header_name' => 'X-Accounting-Tenant-ID',
            'scramble-openapi.tenant.oauth_parameter' => 'accounting_tenant_id',
            'scramble-openapi.oauth2.scopes' => ['read', 'write'],
        ]);

        $response = $this->get('/docs/swagger');

        $response->assertOk();
        $response->assertViewHas('oauthScheme', 'sso');
        $response->assertViewHas('tenantEnabled', true);
        $response->assertViewHas('tenantId', 'accounting-swagger');
        $response->assertViewHas('tenantScheme', 'tenant');
        $response->assertViewHas('tenantHeaderName', 'X-Accounting-Tenant-ID');
        $response->assertViewHas('oauthTenantParameter', 'accounting_tenant_id');
        $response->assertViewHas('swaggerUiDistVersion', '5.20.1');
        $response->assertViewHas('oauthScopes', ['read', 'write']);
        $response->assertSee('accounting-swagger', false);
        $response->assertSee('X-Accounting-Tenant-ID', false);
        $response->assertSee('accounting_tenant_id', false);
        $response->assertSee('swagger-ui-dist@5.20.1', false);
        $response->assertSee('"read"', false);
        $response->assertSee('"write"', false);
    }
}
