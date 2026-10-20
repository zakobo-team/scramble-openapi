<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Unit;

use Dedoc\Scramble\Support\Generator\InfoObject;
use Dedoc\Scramble\Support\Generator\OpenApi;
use PHPUnit\Framework\Attributes\Test;
use Zakobo\ScrambleOpenApi\OpenApi\ScrambleAuthentication;
use Zakobo\ScrambleOpenApi\Tests\TestCase;

class ScrambleAuthenticationTest extends TestCase
{
    #[Test]
    public function it_documents_oauth2_as_authorization_code_for_pkce(): void
    {
        $spec = $this->openApi()->toArray();
        $oauth2 = $spec['components']['securitySchemes']['oauth2'];

        $this->assertSame('oauth2', $oauth2['type']);
        $this->assertArrayHasKey('authorizationCode', $oauth2['flows']);
        $this->assertArrayNotHasKey('clientCredentials', $oauth2['flows']);
    }

    #[Test]
    public function it_derives_the_oauth2_urls_from_the_auth_server_url(): void
    {
        config(['scramble-openapi.oauth2.server_url' => 'https://auth.example.test/']);

        $flow = $this->openApi()->toArray()['components']['securitySchemes']['oauth2']['flows']['authorizationCode'];

        $this->assertSame('https://auth.example.test/oauth/authorize', $flow['authorizationUrl']);
        $this->assertSame('https://auth.example.test/oauth/token', $flow['tokenUrl']);
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
        config(['scramble-openapi.tenant.enabled' => true]);

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
    public function it_builds_the_scalar_login_with_the_tenant_header_and_query_when_tenant_support_is_enabled(): void
    {
        config([
            'app.url' => 'https://api.example.test/',
            'scramble-openapi.oauth2.client_id' => 'client-id',
            'scramble-openapi.tenant.enabled' => true,
            'scramble-openapi.tenant.id' => 'swagger',
        ]);

        $this->assertSame([
            'preferredSecurityScheme' => [['oauth2', 'tenantHeader']],
            'securitySchemes' => [
                'tenantHeader' => [
                    'name' => 'X-Tenant-ID',
                    'in' => 'header',
                    'value' => 'swagger',
                ],
                'oauth2' => [
                    'flows' => [
                        'authorizationCode' => [
                            'x-scalar-client-id' => 'client-id',
                            'x-usePkce' => 'SHA-256',
                            'x-scalar-redirect-uri' => 'https://api.example.test/oauth2-redirect.html',
                            'x-scalar-security-query' => ['tenant_id' => 'swagger'],
                        ],
                    ],
                ],
            ],
        ], ScrambleAuthentication::scalarAuthentication());
    }

    #[Test]
    public function it_builds_the_scalar_login_without_tenant_when_tenant_support_is_disabled(): void
    {
        config([
            'app.url' => 'https://api.example.test',
            'scramble-openapi.oauth2.client_id' => 'client-id',
        ]);

        $this->assertSame([
            'preferredSecurityScheme' => [['oauth2']],
            'securitySchemes' => [
                'oauth2' => [
                    'flows' => [
                        'authorizationCode' => [
                            'x-scalar-client-id' => 'client-id',
                            'x-usePkce' => 'SHA-256',
                            'x-scalar-redirect-uri' => 'https://api.example.test/oauth2-redirect.html',
                        ],
                    ],
                ],
            ],
        ], ScrambleAuthentication::scalarAuthentication());
    }

    private function openApi(): OpenApi
    {
        $openApi = OpenApi::make('3.1.0')
            ->setInfo(InfoObject::make('Test API'));

        app(ScrambleAuthentication::class)($openApi);

        return $openApi;
    }
}
