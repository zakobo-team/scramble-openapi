# Zakobo Scramble OpenAPI

Zakobo OpenAPI conventions, SSO login in Scalar, and Laravel Boost guidance for `dedoc/scramble`.

## Install

```bash
composer require dedoc/scramble zakobo/scramble-openapi
```

Laravel discovers the package. The package configures Scramble. The app needs no service provider, route, or view for it.

Set the Scramble renderer to Scalar in `config/scramble.php`:

```php
'renderer' => 'scalar',
```

Scalar is the only supported docs UI.
Keep `proxyUrl` set to `null` in `renderers.scalar`. The Scalar default sends Try it requests through a public proxy.

The package adds these routes:

- `GET /docs/api` shows the Scalar docs page.
- `GET /docs/api.json` returns the OpenAPI document. Scramble owns this route.
- `GET /oauth2-redirect.html` returns a blank page for the OAuth popup.

When Scramble dev tools are on, `/docs/api` builds the document live.
When dev tools are off, `/docs/api` shows the exported file `public/docs/api.json`.
Create the file with `php artisan scramble:export --path=public/docs/api.json`.

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=scramble-openapi-config
```

The file has three groups:

```php
return [
    'tenant' => [
        'enabled' => false,
        'id' => 'swagger',
    ],

    'oauth2' => [
        'client_id' => env('ZAKOBO_AUTH_AC_CLIENT_ID'),
        'server_url' => env('ZAKOBO_AUTH_URL', 'https://auth.zakobo.test'),
    ],

    'security' => [
        'api_prefix' => 'api/v4/',
        'tenant_only_uri_patterns' => [],
    ],
];
```

Laravel merges package config one level deep.
If the app sets a group, the app group replaces the whole package group.
Set all keys of a group in the app file.

The package derives these values:

- Authorization URL: `{oauth2.server_url}/oauth/authorize`.
- Token URL: `{oauth2.server_url}/oauth/token`.
- Redirect URL: `{APP_URL}/oauth2-redirect.html`.

The tenant header is `X-Tenant-ID`. The OAuth tenant query parameter is `tenant_id`.

## Scalar login

The login uses OAuth2 Authorization Code with PKCE.

1. The user selects the OAuth2 login in Scalar.
2. Scalar opens a popup to the authorization URL.
3. The user logs in on the Auth server.
4. The Auth server sends the popup to `/oauth2-redirect.html`.
5. Scalar reads the code from the popup URL and gets the token.

With `tenant.enabled`, Scalar sends the query parameter `tenant_id` and the header `X-Tenant-ID`. Both use the value of `tenant.id`.

Set up the Auth server:

- Register `{APP_URL}/oauth2-redirect.html` as a redirect URI of the OAuth client.
- Allow the docs origin in the CORS settings.

## Redirect page

`/oauth2-redirect.html` is a blank HTML page with no script.
The path must not change. The OAuth clients have it registered.

## Transformers

The package always uses `preferPatchMethod()`. It adds these transformers:

- `ScrambleSecurityRequirements` sets the security of each operation under `security.api_prefix`.
- `ScrambleAuthentication` adds the `oauth2` scheme and, with tenant support, the `tenantHeader` scheme.
- `JsonApiErrorResponses` adds the JSON:API error responses.
- `DocumentUuidRouteParameters` documents UUID route model parameters as `uuid` strings.
- `RemoveRecursiveIncludeEnumValues` removes recursive relationship paths from include enums.
- `DocumentFilterEnumSchemas` adds the `*FilterField` and `*FilterParameter` enums for JSON:API filters.

The package also caches the JSON:API resource parameters for each resource class.
The cache lives for one PHP process.

## AI agents

The package ships a Laravel Boost guideline and the skill `scramble-openapi-development`.

Run `php artisan boost:install` or `php artisan boost:update` to install them.
The package must be a direct dependency of the app.

## Testing

```bash
composer lint:check
composer analyse
composer test
```

GitHub Actions runs all three checks on pull requests and pushes to `main`.
