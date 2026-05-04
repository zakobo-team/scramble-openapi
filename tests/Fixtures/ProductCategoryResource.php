<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Fixtures;

use Illuminate\Http\Request;
use Zakobo\JsonApiQuery\JsonApiQueryResource;

class ProductCategoryResource extends JsonApiQueryResource
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
}
