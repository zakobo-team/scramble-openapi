<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Http\Controllers;

use Illuminate\Http\Response;

class OAuthRedirectController
{
    public function __invoke(): Response
    {
        return response()
            ->view('scramble-openapi::swagger-oauth2-redirect')
            ->header('Content-Type', 'text/html');
    }
}
