<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Unit;

use Dedoc\Scramble\GeneratorConfig;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Path;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Zakobo\ScrambleOpenApi\OpenApi\DocumentUuidRouteParameters;
use Zakobo\ScrambleOpenApi\Tests\Fixtures\UuidRouteParameterController;
use Zakobo\ScrambleOpenApi\Tests\TestCase;

class DocumentUuidRouteParametersTest extends TestCase
{
    #[Test]
    public function it_documents_uuid_route_model_parameters_as_uuid_strings(): void
    {
        Route::patch('api/v1/users/{user}', [UuidRouteParameterController::class, 'show']);

        $parameter = $this->pathParameter('user');
        $document = $this->documentWith('patch', 'v1/users/{user}', $parameter);

        $this->transform($document);

        $this->assertSame([
            'type' => 'string',
            'format' => 'uuid',
        ], $parameter->schema?->toArray());
    }

    #[Test]
    public function it_matches_snake_route_parameters_to_camel_controller_parameters(): void
    {
        Route::patch('api/v1/oauth-clients/{oauth_client}', [UuidRouteParameterController::class, 'showSnakeCase']);

        $parameter = $this->pathParameter('oauth_client');
        $document = $this->documentWith('patch', 'v1/oauth-clients/{oauth_client}', $parameter);

        $this->transform($document);

        $this->assertSame([
            'type' => 'string',
            'format' => 'uuid',
        ], $parameter->schema?->toArray());
    }

    #[Test]
    public function it_leaves_non_uuid_route_model_parameters_unchanged(): void
    {
        Route::patch('api/v1/products/{product}', [UuidRouteParameterController::class, 'showIntegerKey']);

        $parameter = $this->pathParameter('product');
        $document = $this->documentWith('patch', 'v1/products/{product}', $parameter);

        $this->transform($document);

        $this->assertSame([
            'type' => 'integer',
        ], $parameter->schema?->toArray());
    }

    #[Test]
    public function it_leaves_scalar_route_parameters_unchanged(): void
    {
        Route::delete('oauth/tokens/{token_id}', [UuidRouteParameterController::class, 'destroyToken']);

        $parameter = $this->pathParameter('token_id');
        $document = $this->documentWith('delete', 'oauth/tokens/{token_id}', $parameter);

        $this->transform($document);

        $this->assertSame([
            'type' => 'integer',
        ], $parameter->schema?->toArray());
    }

    private function pathParameter(string $name): Parameter
    {
        return Parameter::make($name, 'path')
            ->setSchema(Schema::fromType(new IntegerType));
    }

    private function documentWith(string $method, string $path, Parameter $parameter): OpenApi
    {
        $operation = Operation::make($method)
            ->setPath($path)
            ->addParameters([$parameter]);

        return OpenApi::make('3.1.0')
            ->addPath(Path::make($path)->addOperation($operation));
    }

    private function transform(OpenApi $document): void
    {
        (new DocumentUuidRouteParameters)->handle(
            $document,
            new OpenApiContext($document, new GeneratorConfig),
        );
    }
}
