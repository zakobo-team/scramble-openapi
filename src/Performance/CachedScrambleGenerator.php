<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Performance;

use Dedoc\Scramble\Generator;
use Dedoc\Scramble\GeneratorConfig;
use Dedoc\Scramble\Support\OperationBuilder;
use Symfony\Component\Finder\Finder;

/**
 * Caches the generated OpenAPI document on disk to avoid the multi-second
 * regeneration cost on every `/docs/api` and `/docs/api.json` request.
 *
 * The cache is keyed by a signature derived from mtimes of source files that
 * influence the generated document (typically PHP under `app/` and `routes/`,
 * plus relevant config files). Any change invalidates the cache.
 *
 * The cached payload is a plain array, which round-trips correctly through
 * `@json($spec)` in the docs view, `response()->json(...)` in the JSON
 * route, and `json_encode(...)` in the `scramble:export` command.
 */
class CachedScrambleGenerator extends Generator
{
    /**
     * @param  list<string>  $watchedPaths  Files or directories whose mtimes invalidate the cache.
     */
    public function __construct(
        OperationBuilder $operationBuilder,
        private readonly string $cacheFile,
        private readonly array $watchedPaths,
    ) {
        parent::__construct($operationBuilder);
    }

    /**
     * @return array<string, mixed>
     */
    public function __invoke(?GeneratorConfig $config = null): array
    {
        $signature = $this->signature();

        $cached = $this->readCache($signature);

        if ($cached !== null) {
            return $cached;
        }

        $document = parent::__invoke($config);

        $serialized = json_decode((string) json_encode($document), associative: true);

        if (! is_array($serialized)) {
            return [];
        }

        $this->writeCache($signature, $serialized);

        return $serialized;
    }

    private function signature(): string
    {
        $maxMtime = 0;
        $fileCount = 0;

        foreach ($this->watchedPaths as $path) {
            if (! file_exists($path)) {
                continue;
            }

            if (is_file($path)) {
                $maxMtime = max($maxMtime, (int) filemtime($path));
                $fileCount++;

                continue;
            }

            $finder = (new Finder)->files()->in($path)->name('*.php');

            foreach ($finder as $file) {
                $maxMtime = max($maxMtime, $file->getMTime());
                $fileCount++;
            }
        }

        return sprintf('%d:%d', $maxMtime, $fileCount);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readCache(string $signature): ?array
    {
        if (! is_file($this->cacheFile)) {
            return null;
        }

        $contents = (string) file_get_contents($this->cacheFile);
        $payload = json_decode($contents, associative: true);

        if (! is_array($payload) || ($payload['signature'] ?? null) !== $signature) {
            return null;
        }

        $document = $payload['document'] ?? null;

        return is_array($document) ? $document : null;
    }

    /**
     * @param  array<string, mixed>  $document
     */
    private function writeCache(string $signature, array $document): void
    {
        $directory = dirname($this->cacheFile);

        if (! is_dir($directory)) {
            mkdir($directory, recursive: true);
        }

        file_put_contents(
            $this->cacheFile,
            (string) json_encode(['signature' => $signature, 'document' => $document]),
        );
    }
}
