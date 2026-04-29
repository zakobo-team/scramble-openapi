<?php

declare(strict_types=1);

namespace Zakobo\ScrambleSsoAuthDriver\Tests\Unit;

use Dedoc\Scramble\Support\Generator\InfoObject;
use Dedoc\Scramble\Support\Generator\OpenApi;
use PHPUnit\Framework\Attributes\Test;
use Zakobo\ScrambleSsoAuthDriver\OpenApi\ScrambleAuthentication;
use Zakobo\ScrambleSsoAuthDriver\Tests\TestCase;

class ScrambleAuthenticationTest extends TestCase
{
    #[Test]
    public function it_documents_oauth2_as_authorization_code_for_pkce(): void
    {
        config([
            'scramble-sso-auth-driver.oauth2.authorization_url' => 'https://auth.example.test/oauth/authorize',
            'scramble-sso-auth-driver.oauth2.token_url' => 'https://auth.example.test/oauth/token',
        ]);

        $spec = $this->openApi()->toArray();
        $oauth2 = $spec['components']['securitySchemes']['oauth2'];

        $this->assertSame('oauth2', $oauth2['type']);
        $this->assertArrayHasKey('authorizationCode', $oauth2['flows']);
        $this->assertArrayNotHasKey('clientCredentials', $oauth2['flows']);
        $this->assertSame('https://auth.example.test/oauth/authorize', $oauth2['flows']['authorizationCode']['authorizationUrl']);
        $this->assertSame('https://auth.example.test/oauth/token', $oauth2['flows']['authorizationCode']['tokenUrl']);
    }

    #[Test]
    public function it_documents_oauth2_security_by_default(): void
    {
        $spec = $this->openApi()->toArray();

        $this->assertArrayNotHasKey('tenantHeader', $spec['components']['securitySchemes']);
        $this->assertSame([
            'oauth2' => [],
        ], $spec['security'][0]);
    }

    #[Test]
    public function it_documents_tenant_header_security_when_tenant_support_is_enabled(): void
    {
        config(['scramble-sso-auth-driver.tenant.enabled' => true]);

        $spec = $this->openApi()->toArray();
        $tenantHeader = $spec['components']['securitySchemes']['tenantHeader'];

        $this->assertSame('apiKey', $tenantHeader['type']);
        $this->assertSame('header', $tenantHeader['in']);
        $this->assertSame('X-Tenant-ID', $tenantHeader['name']);
        $this->assertSame([
            'oauth2' => [],
            'tenantHeader' => [],
        ], $spec['security'][0]);
    }

    #[Test]
    public function it_allows_security_scheme_names_to_be_configured(): void
    {
        config([
            'scramble-sso-auth-driver.swagger_ui.oauth_scheme' => 'sso',
            'scramble-sso-auth-driver.tenant.enabled' => true,
            'scramble-sso-auth-driver.tenant.scheme' => 'tenant',
        ]);

        $spec = $this->openApi()->toArray();

        $this->assertArrayHasKey('sso', $spec['components']['securitySchemes']);
        $this->assertArrayHasKey('tenant', $spec['components']['securitySchemes']);
        $this->assertSame([
            'sso' => [],
            'tenant' => [],
        ], $spec['security'][0]);
    }

    #[Test]
    public function it_allows_the_tenant_header_name_to_be_configured(): void
    {
        config([
            'scramble-sso-auth-driver.tenant.enabled' => true,
            'scramble-sso-auth-driver.tenant.header_name' => 'X-Accounting-Tenant-ID',
        ]);

        $spec = $this->openApi()->toArray();

        $this->assertSame('X-Accounting-Tenant-ID', $spec['components']['securitySchemes']['tenantHeader']['name']);
    }

    private function openApi(): OpenApi
    {
        $openApi = OpenApi::make('3.1.0')
            ->setInfo(InfoObject::make('Test API'));

        app(ScrambleAuthentication::class)($openApi);

        return $openApi;
    }
}
