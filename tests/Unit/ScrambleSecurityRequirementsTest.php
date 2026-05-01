<?php

declare(strict_types=1);

namespace Zakobo\ScrambleSsoAuthDriver\Tests\Unit;

use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\RouteInfo;
use Illuminate\Routing\Route;
use PHPUnit\Framework\Attributes\Test;
use Zakobo\ScrambleSsoAuthDriver\OpenApi\ScrambleSecurityRequirements;
use Zakobo\ScrambleSsoAuthDriver\Tests\TestCase;

class ScrambleSecurityRequirementsTest extends TestCase
{
    #[Test]
    public function it_requires_only_the_tenant_header_for_tenant_public_routes(): void
    {
        config([
            'scramble-sso-auth-driver.tenant.enabled' => true,
            'scramble-sso-auth-driver.security.tenant_only_uri_patterns' => ['api/v4/pa/*'],
        ]);

        $operation = Operation::make('GET');

        app(ScrambleSecurityRequirements::class)->handle($operation, $this->routeInfo('api/v4/pa/countries'));

        $this->assertSame([
            ['tenantHeader' => []],
        ], array_map(fn ($security) => $security->toArray(), $operation->security ?? []));
    }

    #[Test]
    public function it_preserves_endpoints_that_scramble_has_marked_as_unauthenticated(): void
    {
        config([
            'scramble-sso-auth-driver.tenant.enabled' => true,
            'scramble-sso-auth-driver.security.tenant_only_uri_patterns' => ['api/v4/pa/*'],
        ]);

        $operation = Operation::make('GET');
        $operation->security = [];

        app(ScrambleSecurityRequirements::class)->handle($operation, $this->routeInfo('api/v4/pa/countries'));

        $this->assertSame([], $operation->security);
    }

    #[Test]
    public function it_requires_oauth2_and_the_tenant_header_for_authenticated_api_routes(): void
    {
        config(['scramble-sso-auth-driver.tenant.enabled' => true]);

        $operation = Operation::make('GET');

        app(ScrambleSecurityRequirements::class)->handle($operation, $this->routeInfo('api/v4/ra/users'));

        $this->assertSame([
            [
                'oauth2' => [],
                'tenantHeader' => [],
            ],
        ], array_map(fn ($security) => $security->toArray(), $operation->security ?? []));
    }

    #[Test]
    public function it_leaves_non_api_routes_untouched(): void
    {
        $operation = Operation::make('GET');

        app(ScrambleSecurityRequirements::class)->handle($operation, $this->routeInfo('docs/api'));

        $this->assertNull($operation->security);
    }

    #[Test]
    public function it_allows_tenant_only_uri_patterns_to_be_configured(): void
    {
        config([
            'scramble-sso-auth-driver.tenant.enabled' => true,
            'scramble-sso-auth-driver.security.tenant_only_uri_patterns' => ['api/v4/public/*'],
        ]);
        $operation = Operation::make('GET');

        app(ScrambleSecurityRequirements::class)->handle($operation, $this->routeInfo('api/v4/public/countries'));

        $this->assertSame([
            ['tenantHeader' => []],
        ], array_map(fn ($security) => $security->toArray(), $operation->security ?? []));
    }

    #[Test]
    public function it_requires_only_oauth2_when_tenant_support_is_disabled(): void
    {
        $operation = Operation::make('GET');

        app(ScrambleSecurityRequirements::class)->handle($operation, $this->routeInfo('api/v4/ra/users'));

        $this->assertSame([
            ['oauth2' => []],
        ], array_map(fn ($security) => $security->toArray(), $operation->security ?? []));
    }

    private function routeInfo(string $uri): RouteInfo
    {
        return new RouteInfo(new Route(['GET'], $uri, fn () => null), 'GET');
    }
}
