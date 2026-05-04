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
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\Generator\Types\Type;
use PHPUnit\Framework\Attributes\Test;
use Zakobo\ScrambleOpenApi\OpenApi\RemoveRecursiveIncludeEnumValues;
use Zakobo\ScrambleOpenApi\Tests\TestCase;

class RemoveRecursiveIncludeEnumValuesTest extends TestCase
{
    #[Test]
    public function it_removes_recursive_include_enum_values_without_removing_nested_different_relationships(): void
    {
        $parameter = Parameter::make('include', 'query')
            ->setExplode(false)
            ->setSchema(Schema::fromType(
                (new ArrayType)->setItems((new StringType)->enum([
                    'parent',
                    'parent.parent',
                    'parent.children',
                    'county.municipalities.cities',
                    'county.municipalities.county',
                ])),
            ));

        $document = $this->documentWith($parameter);

        (new RemoveRecursiveIncludeEnumValues)->handle(
            $document,
            new OpenApiContext($document, new GeneratorConfig),
        );

        $includeItems = $this->arrayItemsType($parameter);

        $this->assertSame([
            'parent',
            'parent.children',
            'county.municipalities.cities',
        ], $includeItems->enum);

        $this->assertStringContainsString('parent.parent', $parameter->description);
        $this->assertStringContainsString('Use dotted include syntax when needed.', $parameter->description);
    }

    #[Test]
    public function it_does_not_touch_non_include_parameters(): void
    {
        $parameter = Parameter::make('sort', 'query')
            ->setSchema(Schema::fromType((new StringType)->enum([
                'parent.parent',
            ])));

        $document = $this->documentWith($parameter);

        (new RemoveRecursiveIncludeEnumValues)->handle(
            $document,
            new OpenApiContext($document, new GeneratorConfig),
        );

        $type = $parameter->schema->type;

        $this->assertInstanceOf(StringType::class, $type);
        $this->assertSame(['parent.parent'], $type->enum);
        $this->assertSame('', $parameter->description);
    }

    private function documentWith(Parameter $parameter): OpenApi
    {
        $operation = Operation::make('get')
            ->setPath('api/v4/test')
            ->addParameters([$parameter]);

        return OpenApi::make('3.1.0')
            ->addPath(Path::make('v4/test')->addOperation($operation));
    }

    private function arrayItemsType(Parameter $parameter): Type
    {
        $type = $parameter->schema->type;

        $this->assertInstanceOf(ArrayType::class, $type);

        return $type->items;
    }
}
