<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Zakobo\ScrambleOpenApi\Tests\TestCase;

class BoostResourcesTest extends TestCase
{
    #[Test]
    public function it_renders_the_core_guideline_without_blade_errors(): void
    {
        $guideline = view()->file(__DIR__.'/../../resources/boost/guidelines/core.blade.php')->render();

        $this->assertStringContainsString('`scramble-openapi-development`', $guideline);
        $this->assertStringContainsString('`scramble.renderers.scalar.proxyUrl`', $guideline);
    }

    #[Test]
    public function it_names_the_skill_after_its_folder(): void
    {
        $skill = (string) file_get_contents(__DIR__.'/../../resources/boost/skills/scramble-openapi-development/SKILL.md');

        $this->assertStringStartsWith("---\nname: scramble-openapi-development\ndescription: ", $skill);
    }
}
