<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Unit;

use Dedoc\Scramble\GeneratorConfig;
use Dedoc\Scramble\Infer;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\TypeTransformer;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\RouteInfo;
use Illuminate\Routing\Route;
use PHPUnit\Framework\Attributes\Test;
use Zakobo\ScrambleOpenApi\OpenApi\JsonApiCollectionOperationTransformer;
use Zakobo\ScrambleOpenApi\Tests\Fixtures\ProductIndexController;
use Zakobo\ScrambleOpenApi\Tests\TestCase;

class JsonApiCollectionOperationTransformerTest extends TestCase
{
    #[Test]
    public function it_documents_json_api_collection_response_and_query_parameters_from_json_api_collection_calls(): void
    {
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
            'filter',
            'sort',
            'include',
            'includeFilter',
            'fields',
            'page[number]',
            'page[size]',
        ], array_map(
            static fn (mixed $parameter): string => $parameter instanceof \Dedoc\Scramble\Support\Generator\Parameter
                ? $parameter->name
                : '',
            $operation->parameters,
        ));
    }
}
