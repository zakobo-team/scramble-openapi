<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Fixtures;

use Illuminate\Http\Request;

class InferredProductIndexController
{
    public function __invoke(Request $request): mixed
    {
        return FakeProduct::query()->jsonApiCollection($request);
    }
}
