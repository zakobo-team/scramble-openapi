<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Fixtures;

class JsonApiResourceController
{
    public function show(): ExampleJsonApiResource
    {
        return new ExampleJsonApiResource(null);
    }

    public function showWithoutQueryString(): ExampleJsonApiResource
    {
        return (new ExampleJsonApiResource(null))->ignoreFieldsAndIncludesInQueryString();
    }
}
