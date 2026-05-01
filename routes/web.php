<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Zakobo\ScrambleOpenApi\Http\Controllers\OAuthRedirectController;
use Zakobo\ScrambleOpenApi\Http\Controllers\SwaggerUiController;

if ((bool) config('scramble-openapi.swagger_ui.enabled', true)) {
    Route::get(config('scramble-openapi.swagger_ui.path', '/docs/swagger'), SwaggerUiController::class)
        ->name('scramble-openapi.swagger-ui');

    Route::get(config('scramble-openapi.swagger_ui.oauth_redirect_path', '/oauth2-redirect.html'), OAuthRedirectController::class)
        ->name('scramble-openapi.oauth2-redirect');

    Route::get(config('scramble-openapi.swagger_ui.legacy_oauth_redirect_path', '/docs/swagger/oauth2-redirect'), OAuthRedirectController::class)
        ->name('scramble-openapi.oauth2-redirect.legacy');
}
