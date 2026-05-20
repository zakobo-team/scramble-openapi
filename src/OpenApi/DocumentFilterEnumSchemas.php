<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\OpenApi;

use Dedoc\Scramble\Contracts\DocumentTransformer;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Path;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Illuminate\Support\Str;
use LogicException;

class DocumentFilterEnumSchemas implements DocumentTransformer
{
    public function handle(OpenApi $document, OpenApiContext $context): void
    {
        foreach ($document->paths as $path) {
            foreach ($path->operations as $operation) {
                $filterParameters = $this->filterParametersFor($operation);

                if ($filterParameters === []) {
                    continue;
                }

                $baseName = $this->schemaBaseName($operation, $path);

                $this->addEnumSchema(
                    $document,
                    "{$baseName}FilterField",
                    $this->filterFieldsFrom($filterParameters),
                    'Generated enum of supported JSON:API filter keys for this operation.',
                );
                $this->addEnumSchema(
                    $document,
                    "{$baseName}FilterParameter",
                    $filterParameters,
                    'Generated enum of supported JSON:API filter query parameter names for this operation.',
                );
            }
        }
    }

    /**
     * @return list<string>
     */
    private function filterParametersFor(Operation $operation): array
    {
        $parameters = [];

        foreach ($operation->parameters as $parameter) {
            if (! $parameter instanceof Parameter || $parameter->in !== 'query') {
                continue;
            }

            $field = $this->filterFieldFrom($parameter->name);

            if ($field === null) {
                continue;
            }

            $parameters[$parameter->name] = $parameter->name;
        }

        return array_values($parameters);
    }

    private function filterFieldFrom(string $parameterName): ?string
    {
        if (! str_starts_with($parameterName, 'filter[') || ! str_ends_with($parameterName, ']')) {
            return null;
        }

        $field = substr($parameterName, 7, -1);

        if ($field === '' || str_contains($field, '[') || str_contains($field, ']')) {
            return null;
        }

        return $field;
    }

    /**
     * @param  list<string>  $filterParameters
     * @return list<string>
     */
    private function filterFieldsFrom(array $filterParameters): array
    {
        $fields = [];

        foreach ($filterParameters as $parameter) {
            $field = $this->filterFieldFrom($parameter);

            if ($field !== null) {
                $fields[$field] = $field;
            }
        }

        return array_values($fields);
    }

    private function schemaBaseName(Operation $operation, Path $path): string
    {
        $source = $operation->operationId ?: "{$operation->method} {$path->path}";
        $source = preg_replace('/[^A-Za-z0-9]+/', ' ', $source) ?: 'operation';
        $name = Str::studly($source);

        if ($name === '') {
            return 'Operation';
        }

        return preg_match('/^[A-Za-z]/', $name) === 1 ? $name : "Operation{$name}";
    }

    /**
     * @param  list<string>  $values
     */
    private function addEnumSchema(OpenApi $document, string $name, array $values, string $description): void
    {
        $schema = Schema::fromType(
            (new StringType)
                ->enum($values)
                ->setDescription($description),
        );

        if (! $document->components->hasSchema($name)) {
            $document->components->addSchema($name, $schema);

            return;
        }

        $existing = $document->components->getSchema($name);

        if ($this->enumValuesFor($existing) === $values) {
            return;
        }

        throw new LogicException("Cannot add filter enum schema [{$name}] because a different schema already exists.");
    }

    /**
     * @return list<string>|null
     */
    private function enumValuesFor(Schema $schema): ?array
    {
        if (! $schema->type instanceof StringType) {
            return null;
        }

        return $schema->type->enum;
    }
}
