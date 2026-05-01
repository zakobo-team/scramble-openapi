<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Fixtures;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class ProductResource extends JsonApiResource
{
    /**
     * @return array{name: string}
     */
    public function toAttributes(Request $request): array
    {
        return [
            'name' => 'Test product',
        ];
    }
}
