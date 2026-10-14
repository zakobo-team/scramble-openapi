<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Unit;

use Dedoc\Scramble\Scramble;
use PHPUnit\Framework\Attributes\Test;
use Zakobo\ScrambleOpenApi\OpenApi\ScrambleAuthentication;
use Zakobo\ScrambleOpenApi\Tests\TestCase;

class ScrambleOpenApiServiceProviderTest extends TestCase
{
    #[Test]
    public function it_gives_the_scalar_login_to_scramble_before_scramble_boots(): void
    {
        $this->assertSame(
            ScrambleAuthentication::scalarAuthentication(),
            Scramble::getGeneratorConfig(Scramble::DEFAULT_API)->renderer()->get('authentication'),
        );
    }
}
