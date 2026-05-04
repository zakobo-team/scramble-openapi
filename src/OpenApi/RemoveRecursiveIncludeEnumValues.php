<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\OpenApi;

use Dedoc\Scramble\Contracts\DocumentTransformer;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\Type;

class RemoveRecursiveIncludeEnumValues implements DocumentTransformer
{
    public function handle(OpenApi $document, OpenApiContext $context): void
    {
        foreach ($document->paths as $path) {
            foreach ($path->operations as $operation) {
                foreach ($operation->parameters as $parameter) {
                    $enumOwner = $this->enumOwnerFor($parameter);

                    if (! $enumOwner instanceof Type) {
                        continue;
                    }

                    $filtered = collect($enumOwner->enum)
                        ->filter(fn (mixed $value): bool => is_string($value))
                        ->reject(fn (string $value): bool => $this->hasRepeatedSegment($value))
                        ->values()
                        ->all();

                    if ($filtered === $enumOwner->enum) {
                        continue;
                    }

                    $enumOwner->enum($filtered);
                    $parameter->description($this->recursiveIncludeDescription());
                }
            }
        }
    }

    private function enumOwnerFor(Parameter $parameter): ?Type
    {
        if ($parameter->name !== 'include' || $parameter->in !== 'query') {
            return null;
        }

        $type = $parameter->schema?->type;

        return $type instanceof ArrayType ? $type->items : $type;
    }

    private function hasRepeatedSegment(string $value): bool
    {
        $segments = array_filter(explode('.', $value));

        return count($segments) !== count(array_unique($segments));
    }

    private function recursiveIncludeDescription(): string
    {
        return 'Repeated nested includes, for example `parent.parent`, are supported but omitted from enum values. Use dotted include syntax when needed.';
    }
}
