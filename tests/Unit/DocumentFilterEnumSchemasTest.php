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
use Dedoc\Scramble\Support\Generator\Types\StringType;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Zakobo\ScrambleOpenApi\OpenApi\DocumentFilterEnumSchemas;
use Zakobo\ScrambleOpenApi\Tests\TestCase;

class DocumentFilterEnumSchemasTest extends TestCase
{
    #[Test]
    public function it_adds_standard_component_enum_schemas_for_concrete_filter_query_parameters(): void
    {
        $nameFilter = $this->stringQueryParameter('filter[name]');
        $statusFilter = $this->stringQueryParameter('filter[status]');
        $activityNameFilter = $this->stringQueryParameter('filter[activity.name]');
        $publishedAtFilter = $this->stringQueryParameter('filter[published_at]');
        $document = $this->documentWith(
            Operation::make('get')
                ->setOperationId('pa.cities.index')
                ->addParameters([
                    $nameFilter,
                    $statusFilter,
                    $activityNameFilter,
                    $publishedAtFilter,
                    $this->stringQueryParameter('filter'),
                    $this->stringQueryParameter('filter[name][contains]'),
                    $this->stringQueryParameter('sort'),
                    Parameter::make('filter[path]', 'path'),
                ]),
        );

        $this->transform($document);

        $schemas = $document->components->toArray()['schemas'];

        $this->assertSame(
            ['name', 'status', 'activity.name', 'published_at'],
            $schemas['PaCitiesIndexFilterField']['enum'],
        );
        $this->assertSame(
            'Generated enum of supported JSON:API filter keys for this operation.',
            $schemas['PaCitiesIndexFilterField']['description'],
        );
        $this->assertSame(
            ['filter[name]', 'filter[status]', 'filter[activity.name]', 'filter[published_at]'],
            $schemas['PaCitiesIndexFilterParameter']['enum'],
        );
        $this->assertSame(
            'Generated enum of supported JSON:API filter query parameter names for this operation.',
            $schemas['PaCitiesIndexFilterParameter']['description'],
        );
        $this->assertSame(['type' => 'string'], $nameFilter->schema->toArray());
        $this->assertSame(['type' => 'string'], $statusFilter->schema->toArray());
    }

    #[Test]
    public function it_uses_a_stable_method_and_path_schema_name_when_operation_id_is_missing(): void
    {
        $document = $this->documentWith(
            Operation::make('get')
                ->addParameters([
                    $this->stringQueryParameter('filter[name]'),
                ]),
            '/v4/pa/cities',
        );

        $this->transform($document);

        $schemas = $document->components->toArray()['schemas'];

        $this->assertArrayHasKey('GetV4PaCitiesFilterField', $schemas);
        $this->assertArrayHasKey('GetV4PaCitiesFilterParameter', $schemas);
    }

    #[Test]
    public function it_does_not_add_filter_enum_schemas_when_an_operation_has_no_concrete_filters(): void
    {
        $document = $this->documentWith(
            Operation::make('get')
                ->setOperationId('pa.cities.index')
                ->addParameters([
                    $this->stringQueryParameter('filter'),
                    $this->stringQueryParameter('filter[name][contains]'),
                    $this->stringQueryParameter('sort'),
                ]),
        );

        $this->transform($document);

        $this->assertSame([], $document->components->toArray());
    }

    #[Test]
    public function it_leaves_existing_identical_schemas_unchanged(): void
    {
        $document = $this->documentWith(
            Operation::make('get')
                ->setOperationId('pa.cities.index')
                ->addParameters([
                    $this->stringQueryParameter('filter[name]'),
                ]),
        );
        $document->components->addSchema(
            'PaCitiesIndexFilterField',
            Schema::fromType((new StringType)->enum(['name'])),
        );
        $document->components->addSchema(
            'PaCitiesIndexFilterParameter',
            Schema::fromType((new StringType)->enum(['filter[name]'])),
        );

        $this->transform($document);

        $schemas = $document->components->toArray()['schemas'];

        $this->assertSame(['name'], $schemas['PaCitiesIndexFilterField']['enum']);
        $this->assertSame(['filter[name]'], $schemas['PaCitiesIndexFilterParameter']['enum']);
    }

    #[Test]
    public function it_fails_loudly_when_a_different_schema_already_uses_the_generated_name(): void
    {
        $document = $this->documentWith(
            Operation::make('get')
                ->setOperationId('pa.cities.index')
                ->addParameters([
                    $this->stringQueryParameter('filter[name]'),
                ]),
        );
        $document->components->addSchema(
            'PaCitiesIndexFilterField',
            Schema::fromType((new StringType)->enum(['status'])),
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot add filter enum schema [PaCitiesIndexFilterField]');

        $this->transform($document);
    }

    #[Test]
    public function it_fails_loudly_when_a_different_filter_parameter_schema_already_uses_the_generated_name(): void
    {
        $document = $this->documentWith(
            Operation::make('get')
                ->setOperationId('pa.cities.index')
                ->addParameters([
                    $this->stringQueryParameter('filter[name]'),
                ]),
        );
        $document->components->addSchema(
            'PaCitiesIndexFilterParameter',
            Schema::fromType((new StringType)->enum(['filter[status]'])),
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot add filter enum schema [PaCitiesIndexFilterParameter]');

        $this->transform($document);
    }

    private function stringQueryParameter(string $name): Parameter
    {
        return Parameter::make($name, 'query')
            ->setSchema(Schema::fromType(new StringType));
    }

    private function documentWith(Operation $operation, string $path = 'v4/test'): OpenApi
    {
        return OpenApi::make('3.1.0')
            ->addPath(Path::make($path)->addOperation($operation));
    }

    private function transform(OpenApi $document): void
    {
        (new DocumentFilterEnumSchemas)->handle(
            $document,
            new OpenApiContext($document, new GeneratorConfig),
        );
    }
}
