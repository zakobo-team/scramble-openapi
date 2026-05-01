<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Zakobo\ScrambleOpenApi\Tests\TestCase;

class DisabledSwaggerUiTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('scramble-openapi.swagger_ui.enabled', false);
    }

    #[Test]
    public function it_does_not_register_swagger_ui_routes_when_disabled(): void
    {
        $this->get('/docs/swagger')->assertNotFound();
        $this->get('/oauth2-redirect.html')->assertNotFound();
    }
}
