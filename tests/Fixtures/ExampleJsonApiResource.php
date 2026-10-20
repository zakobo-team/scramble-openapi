<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Fixtures;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class ExampleJsonApiResource extends JsonApiResource
{
    /** @return array<string, string> */
    public function toAttributes(Request $request): array
    {
        return ['name' => 'Example'];
    }
}
