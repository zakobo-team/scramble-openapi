<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Fixtures;

use Illuminate\Http\Request;

class ProductCategoryIndexController
{
    public function __invoke(Request $request): mixed
    {
        return FakeProductCategory::query()->jsonApiCollection(ProductCategoryWithProductsResource::class, $request);
    }
}
