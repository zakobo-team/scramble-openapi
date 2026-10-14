<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Zakobo\ScrambleOpenApi\OpenApi\ScrambleAuthentication;

Route::get(ScrambleAuthentication::REDIRECT_PATH, fn () => response('<!doctype html><title>OAuth2 redirect</title>'))
    ->name('scramble-openapi.oauth2-redirect');
