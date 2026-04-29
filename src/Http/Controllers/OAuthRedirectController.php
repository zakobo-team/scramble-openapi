<?php

declare(strict_types=1);

namespace Zakobo\ScrambleSsoAuthDriver\Http\Controllers;

use Illuminate\Http\Response;

class OAuthRedirectController
{
    public function __invoke(): Response
    {
        return response()
            ->view('scramble-sso-auth-driver::swagger-oauth2-redirect')
            ->header('Content-Type', 'text/html');
    }
}
