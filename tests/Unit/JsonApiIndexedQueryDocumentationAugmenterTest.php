<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Unit;

use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Zakobo\JsonApiQuery\Documentation\JsonApiQueryDocumentationFactory;
use Zakobo\JsonApiQuery\Schema\ResourceSchemaFactory;
use Zakobo\ScrambleOpenApi\OpenApi\JsonApiIndexedQueryDocumentationAugmenter;
use Zakobo\ScrambleOpenApi\Tests\Fixtures\FakeProduct;
use Zakobo\ScrambleOpenApi\Tests\Fixtures\ProductResource;
use Zakobo\ScrambleOpenApi\Tests\TestCase;

class JsonApiIndexedQueryDocumentationAugmenterTest extends TestCase
{
    #[Test]
    public function it_adds_indexed_attribute_query_fields_without_database_schema_lookup(): void
    {
        $request = Request::create('/');
        $model = new FakeProduct;

        $schema = app(ResourceSchemaFactory::class)->fromModel($model, $request, ProductResource::class);
        $documentation = app(JsonApiQueryDocumentationFactory::class)->for($model, ProductResource::class, $request);

        $augmented = app(JsonApiIndexedQueryDocumentationAugmenter::class)
            ->augment($documentation, $schema, $request);

        $this->assertNotContains('name', $documentation->filterFields);
        $this->assertNotContains('sku', $documentation->filterFields);
        $this->assertNotContains('name', $documentation->sortFields);
        $this->assertNotContains('sku', $documentation->sortFields);

        $this->assertSame(['name', 'sku', 'category', 'category.name'], $augmented->filterFields);
        $this->assertSame(['name', 'sku', 'category.name'], $augmented->sortFields);
    }
}
