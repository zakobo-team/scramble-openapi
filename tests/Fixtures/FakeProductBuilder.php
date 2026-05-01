<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * @extends Builder<FakeProduct>
 */
class FakeProductBuilder extends Builder
{
    /**
     * Fixture-only stand-in for the app macro used by Scramble AST inspection.
     *
     * @param  class-string<ProductResource>  $resourceClass
     */
    public function jsonApiCollection(string $resourceClass, Request $request): mixed
    {
        return null;
    }
}
