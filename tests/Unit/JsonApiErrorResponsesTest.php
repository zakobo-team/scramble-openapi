<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Unit;

use Dedoc\Scramble\GeneratorConfig;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\OpenApi;
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
    public function it_does_not_touch_success_response_components(): void
    {
        $document = OpenApi::make('3.1.0');
        $document->components->responses['Success'] = Response::make(200)
            ->setContent('application/json', Schema::fromType(new ObjectType));

        (new JsonApiErrorResponses)->handle($document, new OpenApiContext($document, new GeneratorConfig));

        $this->assertArrayHasKey('application/json', $document->components->responses['Success']->content);
    }
}
