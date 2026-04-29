<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Zakobo\ScrambleSsoAuthDriver\Http\Controllers\OAuthRedirectController;
use Zakobo\ScrambleSsoAuthDriver\Http\Controllers\SwaggerUiController;

if ((bool) config('scramble-sso-auth-driver.swagger_ui.enabled', true)) {
    Route::get(config('scramble-sso-auth-driver.swagger_ui.path', '/docs/swagger'), SwaggerUiController::class)
        ->name('scramble-sso-auth-driver.swagger-ui');

    Route::get(config('scramble-sso-auth-driver.swagger_ui.oauth_redirect_path', '/oauth2-redirect.html'), OAuthRedirectController::class)
        ->name('scramble-sso-auth-driver.oauth2-redirect');

    Route::get(config('scramble-sso-auth-driver.swagger_ui.legacy_oauth_redirect_path', '/docs/swagger/oauth2-redirect'), OAuthRedirectController::class)
        ->name('scramble-sso-auth-driver.oauth2-redirect.legacy');
}
