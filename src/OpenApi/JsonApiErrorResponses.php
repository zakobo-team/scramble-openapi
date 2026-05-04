<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\OpenApi;

use Dedoc\Scramble\Contracts\DocumentTransformer;
use Dedoc\Scramble\Exceptions\OpenApiReferenceTargetNotFoundException;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\MixedType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;

class JsonApiErrorResponses implements DocumentTransformer
{
    private const JSON_API_MEDIA_TYPE = 'application/vnd.api+json';

    /** @var array<int, array{component: string, description: string}> */
    private const ERROR_RESPONSES = [
        400 => ['component' => 'JsonApiBadRequest', 'description' => 'Bad Request'],
        401 => ['component' => 'JsonApiUnauthenticated', 'description' => 'Unauthenticated'],
        403 => ['component' => 'JsonApiForbidden', 'description' => 'Forbidden'],
        404 => ['component' => 'JsonApiNotFound', 'description' => 'Not Found'],
        422 => ['component' => 'JsonApiValidationError', 'description' => 'Validation Error'],
        500 => ['component' => 'JsonApiServerError', 'description' => 'Server Error'],
    ];

    public function handle(OpenApi $document, OpenApiContext $context): void
    {
        $this->ensureStandardErrorResponseComponents($document);

        foreach ($document->components->responses as $response) {
            $this->replaceErrorResponseContent($response);
        }

        foreach ($document->paths as $path) {
            foreach ($path->operations as $operation) {
                foreach ($operation->responses ?? [] as $response) {
                    if ($response instanceof Response) {
                        $this->replaceErrorResponseContent($response);
                    }
                }

                foreach (self::ERROR_RESPONSES as $code => $metadata) {
                    if ($this->hasResponseCode($operation->responses ?? [], $code)) {
                        continue;
                    }

                    $operation->addResponse(new Reference(
                        'responses',
                        $metadata['component'],
                        $document->components,
                    ));
                }
            }
        }
    }

    private function replaceErrorResponseContent(Response $response): void
    {
        if (! is_int($response->code) || ! array_key_exists($response->code, self::ERROR_RESPONSES)) {
            return;
        }

        $response->content = [];
        $response
            ->setDescription($response->description ?: self::ERROR_RESPONSES[$response->code]['description'])
            ->setContent(self::JSON_API_MEDIA_TYPE, Schema::fromType($this->errorDocumentType()));
    }

    private function ensureStandardErrorResponseComponents(OpenApi $document): void
    {
        foreach (self::ERROR_RESPONSES as $code => $metadata) {
            $document->components->responses[$metadata['component']] = Response::make($code)
                ->setDescription($metadata['description'])
                ->setContent(self::JSON_API_MEDIA_TYPE, Schema::fromType($this->errorDocumentType()));
        }
    }

    /**
     * @param  array<int, Response|Reference>  $responses
     */
    private function hasResponseCode(array $responses, int $code): bool
    {
        foreach ($responses as $response) {
            if ($response instanceof Response) {
                if ($response->code === $code) {
                    return true;
                }

                continue;
            }

            try {
                $referencedResponse = $response->resolve();
            } catch (OpenApiReferenceTargetNotFoundException) {
                continue;
            }

            if ($referencedResponse instanceof Response && $referencedResponse->code === $code) {
                return true;
            }
        }

        return false;
    }

    private function errorDocumentType(): ObjectType
    {
        return (new ObjectType)
            ->addProperty('errors', (new ArrayType)->setItems(
                (new ObjectType)
                    ->addProperty('status', new StringType)
                    ->addProperty('title', new StringType)
                    ->addProperty('detail', new StringType)
                    ->addProperty('code', new StringType)
                    ->addProperty('source', $this->errorSourceType())
                    ->addProperty('meta', (new ObjectType)->additionalProperties(new MixedType))
                    ->setRequired(['status', 'title']),
            ))
            ->setRequired(['errors']);
    }

    private function errorSourceType(): ObjectType
    {
        return (new ObjectType)
            ->addProperty('pointer', new StringType)
            ->addProperty('parameter', new StringType)
            ->addProperty('header', new StringType);
    }
}
