<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\OpenApi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use Zakobo\JsonApiQuery\Documentation\JsonApiQueryDocumentation;
use Zakobo\JsonApiQuery\Schema\RelationshipSchema;
use Zakobo\JsonApiQuery\Schema\ResourceSchema;
use Zakobo\JsonApiQuery\Schema\ResourceSchemaFactory;

final class JsonApiIndexedQueryDocumentationAugmenter
{
    public function __construct(
        private readonly ResourceSchemaFactory $resourceSchemas,
    ) {}

    public function augment(
        JsonApiQueryDocumentation $documentation,
        ResourceSchema $resourceSchema,
        Request $request,
    ): JsonApiQueryDocumentation {
        return new JsonApiQueryDocumentation(
            modelClass: $documentation->modelClass,
            resourceClass: $documentation->resourceClass,
            resourceType: $documentation->resourceType,
            filterFields: $this->uniqueFields([
                ...$this->indexedAttributeNamesExcept($resourceSchema, $resourceSchema->excludedFromFilter, $request),
                ...$documentation->filterFields,
                ...$this->relationshipFilterFields($resourceSchema, $request),
            ]),
            sortFields: $this->uniqueFields([
                ...$this->resourceSortFields($resourceSchema, $request),
                ...$documentation->sortFields,
            ]),
            includePaths: $documentation->includePaths,
            includeFilterFields: $documentation->includeFilterFields,
            fieldsets: $documentation->fieldsets,
            defaultPageSize: $documentation->defaultPageSize,
            maxPageSize: $documentation->maxPageSize,
            allowUnpaginated: $documentation->allowUnpaginated,
        );
    }

    /**
     * @param  list<string>  $pathSegments
     * @return list<string>
     */
    private function relationshipFilterFields(
        ResourceSchema $resourceSchema,
        Request $request,
        string $prefix = '',
        array $pathSegments = [],
    ): array {
        $fields = [];

        foreach ($resourceSchema->relationships as $relationshipName => $relationship) {
            if ($this->shouldSkipRelationship($relationshipName, $relationship, $pathSegments)) {
                continue;
            }

            $path = $prefix === '' ? $relationshipName : "{$prefix}.{$relationshipName}";
            $relatedSchema = $this->resourceSchemas->schemaForRelationship($relationship, $request);

            foreach ($this->indexedAttributeNamesExcept($relatedSchema, $relatedSchema->excludedFromFilter, $request) as $attribute) {
                $fields[] = "{$path}.{$attribute}";
            }

            $fields = [
                ...$fields,
                ...$this->relationshipFilterFields(
                    $relatedSchema,
                    $request,
                    $path,
                    [...$pathSegments, $relationshipName],
                ),
            ];
        }

        return $this->uniqueFields($fields);
    }

    /**
     * @return list<string>
     */
    private function resourceSortFields(ResourceSchema $resourceSchema, Request $request): array
    {
        return $this->uniqueFields([
            ...$this->indexedAttributeNamesExcept($resourceSchema, $resourceSchema->excludedFromSorting, $request),
            ...$this->relationshipSortFields($resourceSchema, $request),
        ]);
    }

    /**
     * @return list<string>
     */
    private function relationshipSortFields(ResourceSchema $resourceSchema, Request $request): array
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = $resourceSchema->modelClass;
        $model = new $modelClass;
        $fields = [];

        foreach ($resourceSchema->relationships as $relationshipName => $relationship) {
            if ($relationship->resourceClass === null) {
                continue;
            }

            $relation = Relation::noConstraints(fn (): mixed => $model->{$relationship->relationMethodName}());

            if (! $relation instanceof BelongsTo && ! $relation instanceof HasOne) {
                continue;
            }

            $relatedSchema = $this->resourceSchemas->schemaForRelationship($relationship, $request);

            foreach ($this->indexedAttributeNamesExcept($relatedSchema, $relatedSchema->excludedFromSorting, $request) as $attribute) {
                $fields[] = "{$relationshipName}.{$attribute}";
            }
        }

        return $fields;
    }

    /**
     * @param  list<string>  $excludedAttributes
     * @return list<string>
     */
    private function indexedAttributeNamesExcept(
        ResourceSchema $resourceSchema,
        array $excludedAttributes,
        Request $request,
    ): array {
        /** @var class-string<Model> $modelClass */
        $modelClass = $resourceSchema->modelClass;

        /** @var class-string<JsonApiResource> $resourceClass */
        $resourceClass = $resourceSchema->resourceClass;

        /** @var JsonApiResource $resource */
        $resource = $resourceClass::make(new $modelClass);
        $attributes = $resource->toAttributes($request);

        if (! is_array($attributes)) {
            return [];
        }

        $names = [];

        foreach ($attributes as $key => $value) {
            if (is_int($key) && is_string($value)) {
                $names[] = $value;
            }
        }

        return array_values(array_diff($names, $excludedAttributes));
    }

    /**
     * @param  list<string>  $pathSegments
     */
    private function shouldSkipRelationship(
        string $relationshipName,
        RelationshipSchema $relationship,
        array $pathSegments,
    ): bool {
        return $relationship->resourceClass === null
            || in_array($relationshipName, $pathSegments, true)
            || count($pathSegments) >= max(1, JsonApiResource::$maxRelationshipDepth);
    }

    /**
     * @param  list<string>  $fields
     * @return list<string>
     */
    private function uniqueFields(array $fields): array
    {
        $unique = [];

        foreach ($fields as $field) {
            $unique[$field] = true;
        }

        return array_keys($unique);
    }
}
