<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Unit;

use Dedoc\Scramble\GeneratorConfig;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Path;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use PHPUnit\Framework\Attributes\Test;
use Zakobo\ScrambleOpenApi\OpenApi\JsonApiErrorResponses;
use Zakobo\ScrambleOpenApi\Tests\TestCase;

class JsonApiErrorResponsesTest extends TestCase
{
    #[Test]
    public function it_replaces_laravel_error_response_components_with_json_api_error_documents(): void
    {
        $document = OpenApi::make('3.1.0');
        $document->components->responses['ValidationException'] = Response::make(422)
            ->setDescription('Validation failed')
            ->setContent('application/json', Schema::fromType(
                (new ObjectType)->addProperty('message', new StringType),
            ));

        (new JsonApiErrorResponses)->handle($document, new OpenApiContext($document, new GeneratorConfig));

        $response = $document->components->responses['ValidationException'];

        $this->assertArrayNotHasKey('application/json', $response->content);
        $this->assertArrayHasKey('application/vnd.api+json', $response->content);

        $schema = $response->content['application/vnd.api+json']->toArray();

        $this->assertSame('array', $schema['properties']['errors']['type']);
        $this->assertSame(['errors'], $schema['required']);
        $this->assertSame(['status', 'title'], $schema['properties']['errors']['items']['required']);
    }

    #[Test]
    public function it_replaces_inline_operation_error_responses_with_json_api_error_documents(): void
    {
        $document = OpenApi::make('3.1.0')
            ->addPath(
                Path::make('v1/accounts')
                    ->addOperation(
                        Operation::make('get')
                            ->addResponse(
                                Response::make(400)
                                    ->setDescription('Bad request')
                                    ->setContent('application/json', Schema::fromType(
                                        (new ObjectType)->addProperty('message', new StringType),
                                    )),
                            ),
                    ),
            );

        (new JsonApiErrorResponses)->handle($document, new OpenApiContext($document, new GeneratorConfig));

        $response = $document->paths[0]->operations['get']->responses[0];

        $this->assertArrayNotHasKey('application/json', $response->content);
        $this->assertArrayHasKey('application/vnd.api+json', $response->content);
    }

    #[Test]
    public function it_adds_standard_json_api_error_response_references_to_every_operation(): void
    {
        $document = OpenApi::make('3.1.0')
            ->addPath(
                Path::make('v1/accounts')
                    ->addOperation(
                        Operation::make('get')
                            ->addResponse(Response::make(200)->setContent(
                                'application/vnd.api+json',
                                Schema::fromType(new ObjectType),
                            )),
                    ),
            );

        (new JsonApiErrorResponses)->handle($document, new OpenApiContext($document, new GeneratorConfig));

        $operation = $document->paths[0]->operations['get']->toArray();

        $this->assertArrayHasKey('200', $operation['responses']);
        $this->assertSame('#/components/responses/JsonApiBadRequest', $operation['responses']['400']['$ref']);
        $this->assertSame('#/components/responses/JsonApiUnauthenticated', $operation['responses']['401']['$ref']);
        $this->assertSame('#/components/responses/JsonApiForbidden', $operation['responses']['403']['$ref']);
        $this->assertSame('#/components/responses/JsonApiNotFound', $operation['responses']['404']['$ref']);
        $this->assertSame('#/components/responses/JsonApiValidationError', $operation['responses']['422']['$ref']);
        $this->assertSame('#/components/responses/JsonApiServerError', $operation['responses']['500']['$ref']);
        $this->assertArrayHasKey('application/vnd.api+json', $document->components->responses['JsonApiValidationError']->content);
    }

    #[Test]
    public function it_does_not_touch_success_response_components(): void
    {
        $document = OpenApi::make('3.1.0');
        $document->components->responses['Success'] = Response::make(200)
            ->setContent('application/json', Schema::fromType(new ObjectType));

        (new JsonApiErrorResponses)->handle($document, new OpenApiContext($document, new GeneratorConfig));

        $this->assertArrayHasKey('application/json', $document->components->responses['Success']->content);
    }
}
