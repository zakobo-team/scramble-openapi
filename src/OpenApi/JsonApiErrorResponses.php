<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\OpenApi;

use Dedoc\Scramble\Contracts\DocumentTransformer;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;

class JsonApiErrorResponses implements DocumentTransformer
{
    private const JSON_API_MEDIA_TYPE = 'application/vnd.api+json';

    /** @var array<int|string, string> */
    private const DESCRIPTIONS = [
        400 => 'Bad Request',
        401 => 'Unauthenticated',
        403 => 'Forbidden',
        404 => 'Not Found',
        422 => 'Validation Error',
    ];

    public function handle(OpenApi $document, OpenApiContext $context): void
    {
        foreach ($document->components->responses as $response) {
            if (! array_key_exists($response->code, self::DESCRIPTIONS)) {
                continue;
            }

            $response->content = [];
            $response
                ->setDescription($response->description ?: self::DESCRIPTIONS[$response->code])
                ->setContent(self::JSON_API_MEDIA_TYPE, Schema::fromType($this->errorDocumentType()));
        }
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
                    ->addProperty('source', new ObjectType)
                    ->addProperty('meta', new ObjectType)
                    ->setRequired(['status', 'title']),
            ))
            ->setRequired(['errors']);
    }
}
