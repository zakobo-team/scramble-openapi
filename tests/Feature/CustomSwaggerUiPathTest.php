<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Zakobo\ScrambleOpenApi\Tests\TestCase;

class CustomSwaggerUiPathTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('scramble-openapi.swagger_ui.path', '/internal/swagger');
        $app['config']->set('scramble-openapi.swagger_ui.oauth_redirect_path', '/internal/oauth2-redirect.html');
    }

    #[Test]
    public function it_serves_swagger_ui_and_oauth_redirect_from_custom_paths(): void
    {
        $swaggerResponse = $this->get('/internal/swagger');
        $redirectResponse = $this->get('/internal/oauth2-redirect.html');

        $swaggerResponse->assertOk();
        $swaggerResponse->assertViewHas('oauth2RedirectUrl', url('/internal/oauth2-redirect.html'));
        $redirectResponse->assertOk();
        $redirectResponse->assertSee('swaggerUIRedirectOauth2', false);
    }
}
