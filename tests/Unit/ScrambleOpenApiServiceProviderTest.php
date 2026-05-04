<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Unit;

use Illuminate\Support\ServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use Zakobo\ScrambleOpenApi\ScrambleOpenApiServiceProvider;
use Zakobo\ScrambleOpenApi\Tests\TestCase;

class ScrambleOpenApiServiceProviderTest extends TestCase
{
    #[Test]
    public function it_keeps_legacy_publish_tags_available(): void
    {
        $legacyConfigPublishes = ServiceProvider::pathsToPublish(
            ScrambleOpenApiServiceProvider::class,
            'scramble-sso-auth-driver-config',
        );
        $legacyViewPublishes = ServiceProvider::pathsToPublish(
            ScrambleOpenApiServiceProvider::class,
            'scramble-sso-auth-driver-views',
        );

        $this->assertSame(
            config_path('scramble-sso-auth-driver.php'),
            reset($legacyConfigPublishes),
        );
        $this->assertSame(
            resource_path('views/vendor/scramble-sso-auth-driver'),
            reset($legacyViewPublishes),
        );
    }
}
