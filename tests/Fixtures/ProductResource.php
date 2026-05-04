<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Fixtures;

use Illuminate\Http\Request;
use Zakobo\JsonApiQuery\JsonApiQueryResource;

class ProductResource extends JsonApiQueryResource
{
    /**
     * @return array<int|string, string>
     */
    public function toAttributes(Request $request): array
    {
        return [
            'name',
            'sku',
            'computed' => 'not-a-column',
        ];
    }

    /**
     * @return array<string, class-string<ProductCategoryResource>>
     */
    public function toRelationships(Request $request): array
    {
        return [
            'category' => ProductCategoryResource::class,
        ];
    }

    public array $excludedFromFilter = ['computed'];

    public array $excludedFromSorting = ['computed'];

    public ?int $defaultPageSize = 10;

    public ?int $maxPageSize = 25;
}
