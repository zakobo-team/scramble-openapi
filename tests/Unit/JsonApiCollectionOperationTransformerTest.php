<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Unit;

use Dedoc\Scramble\GeneratorConfig;
use Dedoc\Scramble\Infer;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\Generator\TypeTransformer;
use Dedoc\Scramble\Support\RouteInfo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use PHPUnit\Framework\Attributes\Test;
use Zakobo\ScrambleOpenApi\OpenApi\JsonApiCollectionOperationTransformer;
use Zakobo\ScrambleOpenApi\Tests\Fixtures\ProductIndexController;
use Zakobo\ScrambleOpenApi\Tests\TestCase;

class JsonApiCollectionOperationTransformerTest extends TestCase
{
    #[Test]
    public function it_documents_json_api_collection_response_and_query_parameters_from_json_api_collection_calls(): void
    {
        $this->createJsonApiQueryTables();

        $operation = Operation::make('get')
            ->setPath('api/v4/products')
            ->addResponse(
                Response::make(200)
                    ->setDescription('Array of items')
                    ->setContent('application/json', Schema::fromType(
                        (new ObjectType)->addProperty('data', new StringType),
                    )),
            );

        $openApi = OpenApi::make('3.1.0');

        $transformer = new JsonApiCollectionOperationTransformer(
            app(Infer::class),
            app(TypeTransformer::class, [
                'context' => new OpenApiContext($openApi, new GeneratorConfig),
            ]),
            new GeneratorConfig,
        );

        $transformer->handle(
            $operation,
            new RouteInfo(
                new Route(['GET'], 'api/v4/products', [
                    'uses' => ProductIndexController::class.'@__invoke',
                ]),
                'GET',
            ),
        );

        $response = $operation->responses[0];

        $this->assertInstanceOf(Response::class, $response);
        $this->assertArrayNotHasKey('application/json', $response->content);
        $this->assertArrayHasKey('application/vnd.api+json', $response->content);

        $schema = $response->content['application/vnd.api+json']->toArray();

        $this->assertSame('array', $schema['properties']['data']['type']);
        $this->assertSame('#/components/schemas/ProductResource', $schema['properties']['data']['items']['$ref']);

        $this->assertSame([
            'filter[name]',
            'filter[sku]',
            'filter[category]',
            'filter[category.name]',
            'sort',
            'include',
            'includeFilter[category.name]',
            'fields[products]',
            'fields[product_categories]',
            'page[number]',
            'page[size]',
        ], array_map(
            static fn (mixed $parameter): string => $parameter instanceof Parameter
                ? $parameter->name
                : '',
            $operation->parameters,
        ));

        $nameFilterSchema = $this->parameterSchema($operation, 'filter[name]');
        $relationshipFilterSchema = $this->parameterSchema($operation, 'filter[category.name]');
        $sortSchema = $this->parameterSchema($operation, 'sort');
        $includeSchema = $this->parameterSchema($operation, 'include');
        $includeFilterSchema = $this->parameterSchema($operation, 'includeFilter[category.name]');
        $productFieldsSchema = $this->parameterSchema($operation, 'fields[products]');
        $pageSizeSchema = $this->parameterSchema($operation, 'page[size]');

        $this->assertNull($this->parameter($operation, 'filter'));
        $this->assertSame('string', $nameFilterSchema['type']);
        $this->assertSame('string', $relationshipFilterSchema['type']);
        $this->assertArrayNotHasKey('additionalProperties', $nameFilterSchema);

        $this->assertSame(['name', '-name', 'sku', '-sku', 'category.name', '-category.name'], $sortSchema['items']['enum']);
        $this->assertSame(['category'], $includeSchema['items']['enum']);

        $this->assertSame('string', $includeFilterSchema['type']);
        $this->assertArrayNotHasKey('additionalProperties', $includeFilterSchema);
        $this->assertSame(['name', 'sku', 'computed'], $productFieldsSchema['items']['enum']);
        $this->assertSame(25, $pageSizeSchema['maximum']);

        $this->assertStringContainsString('JSON:API filter for name.', $this->parameterDescription($operation, 'filter[name]'));
        $this->assertStringContainsString('filter[name][eq]', $this->parameterDescription($operation, 'filter[name]'));
        $this->assertStringContainsString('JSON:API filter for category.name.', $this->parameterDescription($operation, 'filter[category.name]'));
        $this->assertStringContainsString('Available sort fields: name, sku, category.name.', $this->parameterDescription($operation, 'sort'));
        $this->assertStringContainsString('Available include paths: category.', $this->parameterDescription($operation, 'include'));
        $this->assertStringContainsString('JSON:API include filter for category.name.', $this->parameterDescription($operation, 'includeFilter[category.name]'));
        $this->assertStringContainsString('Available fields: name, sku, computed.', $this->parameterDescription($operation, 'fields[products]'));
    }

    /**
     * @return array<string, mixed>
     */
    private function parameterSchema(Operation $operation, string $name): array
    {
        $parameter = $this->parameter($operation, $name);

        if ($parameter instanceof Parameter) {
            return $parameter->schema->type->toArray();
        }

        $this->fail("Missing {$name} query parameter.");
    }

    private function parameterDescription(Operation $operation, string $name): string
    {
        $parameter = $this->parameter($operation, $name);

        if ($parameter instanceof Parameter) {
            return $parameter->description;
        }

        $this->fail("Missing {$name} query parameter.");
    }

    private function parameter(Operation $operation, string $name): ?Parameter
    {
        foreach ($operation->parameters as $parameter) {
            if (! $parameter instanceof Parameter || $parameter->name !== $name) {
                continue;
            }

            return $parameter;
        }

        return null;
    }

    private function createJsonApiQueryTables(): void
    {
        DatabaseSchema::create('fake_product_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        DatabaseSchema::create('fake_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('fake_product_category_id')->nullable();
            $table->string('name');
            $table->string('sku');
            $table->timestamps();
        });
    }
}
