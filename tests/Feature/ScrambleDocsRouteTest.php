<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Feature;

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Zakobo\ScrambleOpenApi\Tests\TestCase;

class ScrambleDocsRouteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RestrictedDocsAccess::class);
        config(['scramble.dev_tools.enabled' => false]);
    }

    #[Test]
    public function it_returns_not_found_when_the_exported_document_is_missing(): void
    {
        $this->get('/docs/api')
            ->assertNotFound()
            ->assertSee('Exported OpenAPI document not found. Run php artisan scramble:export --path=public/docs/api.json.', false);
    }

    #[Test]
    public function it_renders_scalar_with_the_exported_document_and_the_sso_login(): void
    {
        $this->writeExportedDocument(['openapi' => '3.1.0', 'info' => ['title' => 'Exported API', 'version' => '1.0.0'], 'paths' => []]);

        $response = $this->get('/docs/api');

        $response->assertOk();
        $response->assertSee('Scalar.createApiReference', false);
        $response->assertSee('Exported API', false);
        $response->assertSee('"x-usePkce":"SHA-256"', false);
    }

    /**
     * @param  array<string, mixed>  $document
     */
    private function writeExportedDocument(array $document): void
    {
        $documentPath = public_path('docs/api.json');
        $directory = dirname($documentPath);
        $directoryExisted = File::isDirectory($directory);

        File::ensureDirectoryExists($directory);
        File::put($documentPath, json_encode($document, JSON_THROW_ON_ERROR));

        $this->beforeApplicationDestroyed(function () use ($documentPath, $directory, $directoryExisted): void {
            File::delete($documentPath);

            if (! $directoryExisted) {
                File::deleteDirectory($directory);
            }
        });
    }
}
