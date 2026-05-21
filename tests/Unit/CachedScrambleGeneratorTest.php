<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Unit;

use Dedoc\Scramble\GeneratorConfig;
use Dedoc\Scramble\Support\OperationBuilder;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Zakobo\ScrambleOpenApi\Performance\CachedScrambleGenerator;
use Zakobo\ScrambleOpenApi\Tests\TestCase;

class CachedScrambleGeneratorTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir().'/scramble-openapi-cache-test-'.uniqid();
        mkdir($this->tempDir, recursive: true);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->tempDir);

        parent::tearDown();
    }

    #[Test]
    public function it_returns_the_cached_document_when_the_signature_matches(): void
    {
        $cacheFile = $this->tempDir.'/spec.json';
        $cachedDocument = ['openapi' => '3.1.0', 'paths' => ['/cached' => []]];

        file_put_contents($cacheFile, (string) json_encode([
            'signature' => '0:0',
            'document' => $cachedDocument,
        ]));

        $generator = new CachedScrambleGenerator(
            operationBuilder: $this->app->make(OperationBuilder::class),
            cacheFile: $cacheFile,
            watchedPaths: [],
        );

        $result = $generator(new GeneratorConfig);

        $this->assertSame($cachedDocument, $result);
    }

    #[Test]
    public function it_invalidates_the_cache_when_the_signature_changes(): void
    {
        $cacheFile = $this->tempDir.'/spec.json';
        file_put_contents($cacheFile, (string) json_encode([
            'signature' => 'stale-signature',
            'document' => ['openapi' => '3.1.0'],
        ]));

        $watchedFile = $this->tempDir.'/watched.php';
        file_put_contents($watchedFile, "<?php\n");

        $generator = new CachedScrambleGenerator(
            operationBuilder: $this->app->make(OperationBuilder::class),
            cacheFile: $cacheFile,
            watchedPaths: [$watchedFile],
        );

        $signatureMethod = new ReflectionMethod(CachedScrambleGenerator::class, 'signature');
        $readCacheMethod = new ReflectionMethod(CachedScrambleGenerator::class, 'readCache');

        $currentSignature = $signatureMethod->invoke($generator);

        $this->assertNotSame('stale-signature', $currentSignature);
        $this->assertNull($readCacheMethod->invoke($generator, $currentSignature));
    }

    private function deleteDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        foreach ((array) scandir($directory) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory.'/'.$entry;

            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }

        rmdir($directory);
    }
}
