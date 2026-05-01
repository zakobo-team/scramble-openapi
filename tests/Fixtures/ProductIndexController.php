<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Fixtures;

use Illuminate\Http\Request;

class ProductIndexController
{
    public function __invoke(Request $request): mixed
    {
        return FakeProduct::query()->jsonApiCollection(ProductResource::class, $request);
    }
}
