<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Feature;

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;
use PHPUnit\Framework\Attributes\Test;
use Zakobo\ScrambleOpenApi\Tests\TestCase;

class ScrambleDocsViewTest extends TestCase
{
    #[Test]
    public function it_auto_configures_the_scramble_docs_route(): void
    {
        $this->withoutMiddleware(RestrictedDocsAccess::class);

        $response = $this->get('/docs/api');

        $response->assertOk();
        $response->assertSee('<elements-api', false);
    }

    #[Test]
    public function it_renders_the_scramble_docs_view_with_the_configured_tenant_header_interceptor(): void
    {
        $response = $this->view('scramble-openapi::scramble-docs', [
            'spec' => ['openapi' => '3.1.0'],
            'config' => [],
            'tenantEnabled' => true,
            'tenantId' => 'swagger',
            'tenantHeaderName' => 'X-Accounting-Tenant-ID',
        ]);

        $response->assertSee('if (tenantEnabled)', false);
        $response->assertSee('headers.set(tenantHeaderName, tenantId)', false);
        $response->assertSee('X-Accounting-Tenant-ID', false);
        $response->assertSee('swagger', false);
    }
}
