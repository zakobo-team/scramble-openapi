<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * @extends Builder<FakeProductCategory>
 */
class FakeProductCategoryBuilder extends Builder
{
    public function jsonApiCollection(string|Request $resourceClass, ?Request $request = null): mixed
    {
        return null;
    }
}
