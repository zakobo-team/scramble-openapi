<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Zakobo\ScrambleOpenApi\Http\Controllers\OAuthRedirectController;
use Zakobo\ScrambleOpenApi\Http\Controllers\SwaggerUiController;

if ((bool) config('scramble-openapi.swagger_ui.enabled', true)) {
    $swaggerUiPath = config('scramble-openapi.swagger_ui.path', '/docs/swagger');
    $oauthRedirectPath = config('scramble-openapi.swagger_ui.oauth_redirect_path', '/oauth2-redirect.html');
    $legacyOauthRedirectPath = config('scramble-openapi.swagger_ui.legacy_oauth_redirect_path', '/docs/swagger/oauth2-redirect');

    Route::get($swaggerUiPath, SwaggerUiController::class)
        ->name('scramble-openapi.swagger-ui');

    Route::get($swaggerUiPath, SwaggerUiController::class)
        ->name('scramble-sso-auth-driver.swagger-ui');

    Route::get($oauthRedirectPath, OAuthRedirectController::class)
        ->name('scramble-openapi.oauth2-redirect');

    Route::get($oauthRedirectPath, OAuthRedirectController::class)
        ->name('scramble-sso-auth-driver.oauth2-redirect');

    Route::get($legacyOauthRedirectPath, OAuthRedirectController::class)
        ->name('scramble-openapi.oauth2-redirect.legacy');

    Route::get($legacyOauthRedirectPath, OAuthRedirectController::class)
        ->name('scramble-sso-auth-driver.oauth2-redirect.legacy');
}
