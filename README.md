# Zakobo Scramble OpenAPI

Zakobo OpenAPI conventions, SSO authentication and Swagger UI integration for Laravel apps using `dedoc/scramble`.

The goal is that an app can install Scramble, install this package, publish config when needed, and get working API docs without copying service providers, routes, views or OpenAPI auth classes into the app.

The package provides:

- Scramble OpenAPI security schemes for OAuth2 Authorization Code with PKCE and tenant header auth.
- Scramble operation security rules for tenant-only and authenticated API routes.
- A Swagger UI route with Zakobo SSO authentication through either `auth-bootstrap` or static OAuth config.
- A direct Authentication/Logout button instead of Swagger UI's default auth modal.
- OAuth2 redirect view for Swagger UI.
- A Stoplight/Scramble docs view with optional tenant header injection.
- JSON:API filter key component schemas derived from the consuming app's generated concrete filter query parameters.
- JSON:API error response schemas.
- Include enum cleanup for recursive relationship paths.

## Quick Start

Install Scramble first, then install this package:

```bash
composer require dedoc/scramble
composer require zakobo/scramble-openapi
```

The package is auto-discovered by Laravel and auto-configures Scramble by default.

It registers these routes:

- `/docs/api`
- `/docs/api.json`
- `/docs/swagger`
- `/oauth2-redirect.html`
- `/docs/swagger/oauth2-redirect` as a legacy callback path

No app service provider, route file, controller, view or OpenAPI auth class is required.

## Required App Configuration

Publish the config when the app needs tenant support, custom paths, custom OAuth URLs, or a fixed Swagger OAuth client:

```bash
php artisan vendor:publish --tag=scramble-openapi-config
```

To make the Swagger UI Authentication button work, configure one of these:

- `swagger_ui.auth_bootstrap_path` when the app exposes Zakobo's `auth-bootstrap` endpoint.
- `oauth2.client_id` when the app uses a fixed Swagger OAuth client.

If neither is configured, the docs still load, but authentication fails with this clear browser error:

```text
Missing OAuth client_id. Configure auth_bootstrap_path or oauth2.client_id.
```

## Non-Tenant App

For an app without tenancy, leave `tenant.enabled` as `false`.

The package will then:

- document OAuth2 only
- avoid tenant OpenAPI security schemes
- avoid injecting tenant headers into API requests
- avoid sending tenant parameters to OAuth

Minimal static OAuth setup:

```php
return [
    'tenant' => [
        'enabled' => false,
    ],

    'oauth2' => [
        'client_id' => env('SCRAMBLE_OPENAPI_OAUTH2_CLIENT_ID'),
        'authorization_url' => env('SCRAMBLE_OPENAPI_OAUTH2_AUTH_URL', 'https://auth.zakobo.test/oauth/authorize'),
        'token_url' => env('SCRAMBLE_OPENAPI_OAUTH2_TOKEN_URL', 'https://auth.zakobo.test/oauth/token'),
    ],
];
```

## Tenant-Aware App

For an app that requires a tenant header, enable tenant support and configure the header and security patterns.

Example:

```php
return [
    'swagger_ui' => [
        'auth_bootstrap_path' => env('SCRAMBLE_OPENAPI_AUTH_BOOTSTRAP_PATH', '/api/v4/pa/auth-bootstrap'),
    ],

    'tenant' => [
        'enabled' => true,
        'id' => env('SCRAMBLE_OPENAPI_TENANT_ID', 'swagger'),
        'scheme' => env('SCRAMBLE_OPENAPI_TENANT_SCHEME', 'tenantHeader'),
        'header_name' => env('SCRAMBLE_OPENAPI_TENANT_HEADER_NAME', 'X-Tenant-ID'),
        'oauth_parameter' => env('SCRAMBLE_OPENAPI_OAUTH_TENANT_PARAMETER', 'tenant_id'),
    ],

    'security' => [
        'api_prefix' => env('SCRAMBLE_OPENAPI_API_PREFIX', 'api/v4/'),
        'tenant_only_uri_patterns' => [
            'api/v4/pa/*',
        ],
    ],
];
```

## Custom Scramble Setup

No app service provider changes are required by default.

By default, the package calls `Scramble::configure()` for the app and applies the Zakobo SSO auth setup.

If an app already has its own Scramble service provider, or needs to add custom Scramble configuration, disable auto configuration:

```php
'scramble' => [
    'auto_configure' => false,
],
```

Then call the configurator manually from the app's Scramble service provider:

```php
use Dedoc\Scramble\Scramble;
use Zakobo\ScrambleOpenApi\ScrambleOpenApi;

ScrambleOpenApi::configure(
    Scramble::configure()
        ->preferPatchMethod()
        // Add app-specific Scramble configuration here.
);
```

This keeps the package-owned OAuth, tenant security, docs view and Swagger UI setup, while still allowing the app to customize Scramble itself.

## Publishable Assets

Publish config:

```bash
php artisan vendor:publish --tag=scramble-openapi-config
```

Publish views only when the app needs to override the package UI:

```bash
php artisan vendor:publish --tag=scramble-openapi-views
```

Legacy publish tags and namespaces from `zakobo/scramble-sso-auth-driver` remain available as a transition path, but new apps should use `zakobo/scramble-openapi`.

There are no provider, controller or route stubs because those are owned by the package.

## Full Config Example

Package defaults are intentionally environment-free so config caching stays safe. Apps should override with their own config file and `env()` values.

Example tenant-aware app override:

```php
return [
    'enabled' => env('SCRAMBLE_OPENAPI_ENABLED', true),

    'scramble' => [
        'auto_configure' => env('SCRAMBLE_OPENAPI_AUTO_CONFIGURE', true),
        'prefer_patch_method' => env('SCRAMBLE_OPENAPI_PREFER_PATCH_METHOD', true),
        'ui_path' => env('SCRAMBLE_OPENAPI_DOCS_PATH', '/docs/api'),
        'document_path' => env('SCRAMBLE_OPENAPI_DOCUMENT_PATH', '/docs/api.json'),
    ],

    'swagger_ui' => [
        'enabled' => env('SCRAMBLE_OPENAPI_SWAGGER_UI_ENABLED', true),
        'path' => env('SCRAMBLE_OPENAPI_SWAGGER_UI_PATH', '/docs/swagger'),
        'spec_path' => env('SCRAMBLE_OPENAPI_SWAGGER_UI_SPEC_PATH', '/docs/api.json'),
        'auth_bootstrap_path' => env('SCRAMBLE_OPENAPI_AUTH_BOOTSTRAP_PATH', '/api/v4/pa/auth-bootstrap'),
        'oauth_redirect_path' => env('SCRAMBLE_OPENAPI_OAUTH_REDIRECT_PATH', '/oauth2-redirect.html'),
        'legacy_oauth_redirect_path' => env('SCRAMBLE_OPENAPI_LEGACY_OAUTH_REDIRECT_PATH', '/docs/swagger/oauth2-redirect'),
        'oauth_scheme' => env('SCRAMBLE_OPENAPI_OAUTH_SCHEME', 'oauth2'),
        'swagger_ui_dist_version' => env('SCRAMBLE_OPENAPI_SWAGGER_UI_DIST_VERSION', '5.20.1'),
    ],

    'tenant' => [
        'enabled' => true,
        'id' => env('SCRAMBLE_OPENAPI_TENANT_ID', 'swagger'),
        'scheme' => env('SCRAMBLE_OPENAPI_TENANT_SCHEME', 'tenantHeader'),
        'header_name' => env('SCRAMBLE_OPENAPI_TENANT_HEADER_NAME', 'X-Tenant-ID'),
        'oauth_parameter' => env('SCRAMBLE_OPENAPI_OAUTH_TENANT_PARAMETER', 'tenant_id'),
    ],

    'oauth2' => [
        'client_id' => env('SCRAMBLE_OPENAPI_OAUTH2_CLIENT_ID'),
        'authorization_url' => env('SCRAMBLE_OPENAPI_OAUTH2_AUTH_URL', 'https://auth.zakobo.test/oauth/authorize'),
        'token_url' => env('SCRAMBLE_OPENAPI_OAUTH2_TOKEN_URL', 'https://auth.zakobo.test/oauth/token'),
        'redirect_url' => env('SCRAMBLE_OPENAPI_OAUTH2_REDIRECT_URL'),
        'scopes' => array_values(array_filter(explode(' ', (string) env('SCRAMBLE_OPENAPI_OAUTH2_SCOPES', '')))),
    ],

    'security' => [
        'api_prefix' => env('SCRAMBLE_OPENAPI_API_PREFIX', 'api/v4/'),
        'tenant_only_uri_patterns' => [
            'api/v4/pa/*',
        ],
    ],
];
```

## SSO Requirements

The Swagger OAuth client in Auth Laravel must allow the configured redirect URI.

Default local callback:

```text
https://api.zakobo.test/oauth2-redirect.html
```

The app must also allow the Swagger/API origin in Auth Laravel CORS configuration.

## Troubleshooting

Authentication button says `Missing OAuth client_id`

Configure either `swagger_ui.auth_bootstrap_path` or `oauth2.client_id`.

Swagger UI opens but API requests get tenant errors

Enable `tenant.enabled` and verify `tenant.id`, `tenant.header_name` and `security.tenant_only_uri_patterns`.

OAuth redirects back with `invalid_grant`

Verify that the OAuth client redirect URI exactly matches the configured callback, including scheme, host and path.

OAuth token request fails with CORS

Allow the API/docs origin in Auth Laravel CORS configuration.

Docs route conflicts with an app route

Change `scramble.ui_path`, `scramble.document_path`, `swagger_ui.path` or `swagger_ui.oauth_redirect_path`.

## Testing

```bash
composer lint:check
composer analyse
composer test
```

GitHub Actions runs all three checks on pull requests and pushes to `main`.
