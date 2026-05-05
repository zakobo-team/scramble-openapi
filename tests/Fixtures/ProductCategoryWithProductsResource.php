<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Fixtures;

use Illuminate\Http\Request;
use Zakobo\JsonApiQuery\JsonApiQueryResource;

class ProductCategoryWithProductsResource extends JsonApiQueryResource
{
    /**
     * @return list<string>
     */
    public function toAttributes(Request $request): array
    {
        return [
            'name',
        ];
    }

    /**
     * @return array<string, class-string<ProductResource>>
     */
    public function toRelationships(Request $request): array
    {
        return [
            'products' => ProductResource::class,
        ];
    }
}
