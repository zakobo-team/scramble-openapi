<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Zakobo\ScrambleOpenApi\Tests\TestCase;

class OAuthRedirectPageTest extends TestCase
{
    #[Test]
    public function it_serves_a_blank_html_page_without_script_on_the_registered_redirect_path(): void
    {
        $response = $this->get('/oauth2-redirect.html');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        $response->assertDontSee('<script', false);
    }

    #[Test]
    public function it_no_longer_serves_the_swagger_ui(): void
    {
        $this->get('/docs/swagger')->assertNotFound();
    }
}
