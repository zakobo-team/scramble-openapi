---
name: scramble-openapi-development
description: Use this skill when you change API endpoints, resources, form requests, or authentication. Use it when OpenAPI output is missing, wrong, or old. Use it for work on the Scramble docs routes, the Scalar login, the zakobo/scramble-openapi package, the Scramble commands, or the Scramble tests.
---

# Scramble OpenAPI development

Scramble reads the application code and builds the OpenAPI document.
The code is the source of truth.
The package `zakobo/scramble-openapi` adds the Zakobo conventions.
If the app has the Scramble skill `scramble-development`, use it together with this skill.

## Rules

- Check the runtime contract first.
- If the runtime behavior is wrong, fix it as an ordinary bug.
- If the behavior is right but Scramble infers it wrong, fix the documentation layer. Use PHPDoc types, narrower declarations that keep the behavior, a narrow attribute, or a Scramble extension.
- Prefer inference over annotation. Types come from return types, resources, Data objects, and validation rules.
- Do not change runtime behavior only to change the docs.
- Do not add manual OpenAPI parameters that Scramble can infer.
- Do not add titles, descriptions, or examples unless the task asks for them. Use `#[Endpoint]` for a title and a description.
- Controller boundaries follow the app architecture. They do not follow the Scramble output. Do not split or merge controllers to shape the docs.
- Keep enum values in PHP backed enums. Scramble documents the cases.
- Keep `scramble.renderers.scalar.proxyUrl` set to `null`.

## Routes

Scalar is the only docs UI.

| Route | What it does |
|---|---|
| `/docs/api` | Shows the Scalar docs page. The package owns this route. |
| `/docs/api.json` | Returns the OpenAPI document. Scramble owns this route. |
| `/oauth2-redirect.html` | Returns a blank page. The package owns this route. The OAuth popup returns to it. |

The `/docs/api` route works in two modes:

- Dev tools on: the route builds the document live. It uses the document cache when the cache is warm.
- Dev tools off: the route shows the exported file `public/docs/api.json`. If the file is missing, the route returns 404 with a message. If the file is not readable, the route returns 500.

Scramble dev tools are on when `scramble.dev_tools.enabled` is true.
The default value is `SCRAMBLE_DEV_TOOLS`. If that variable is not set, the default value is `APP_DEBUG`.

The paths are fixed. Do not change them. The OAuth clients have the redirect path registered.

An app can call `Scramble::configure()->expose(...)` in its own routes. That call replaces the package route.

Set `renderer` to `scalar` in the `config/scramble.php` of the app. The Scramble default renderer does not have the SSO login.

## Scalar login

The login uses OAuth2 Authorization Code with PKCE (SHA-256).

1. The user selects the OAuth2 login in Scalar.
2. Scalar opens a popup to `{oauth2.server_url}/oauth/authorize`.
3. Scalar sends the client ID, the redirect URL, and the PKCE challenge.
4. If tenant support is on, Scalar also sends the query parameter `tenant_id`.
5. The user logs in on the Auth server.
6. The Auth server sends the popup to `{APP_URL}/oauth2-redirect.html`.
7. The redirect page is blank and has no script. Scalar reads the code from the popup URL.
8. Scalar sends the code to `{oauth2.server_url}/oauth/token`.
9. Scalar sends the access token as a bearer token with each Try it request.
10. If tenant support is on, Scalar also sends the header `X-Tenant-ID` with the value of `tenant.id`.

The package builds the Scalar settings in `register()` of its service provider.
It writes them to `scramble.renderers.scalar.authentication`.
Scramble copies `config('scramble')` when it boots. The value must exist before Scramble boots.
The Scramble view `scramble::scalar` passes the settings to `Scalar.createApiReference`.

The Scalar default for `proxyUrl` is `https://proxy.scalar.com`.
With that value, Try it requests go through a third party. The third party can see the token and the API data.
Each app sets `proxyUrl` to `null` in its own `config/scramble.php`. The package never changes this value.

The login needs two things outside the app:

- The OAuth client on the Auth server must have `{APP_URL}/oauth2-redirect.html` as a redirect URI.
- The Auth server must allow the docs origin in its CORS settings.

## Configuration

The file is `config/scramble-openapi.php`. Publish it with `php artisan vendor:publish --tag=scramble-openapi-config`.

| Group | Key | Default | Use |
|---|---|---|---|
| `tenant` | `enabled` | `false` | Turns on the tenant header and the `tenant_id` query. |
| `tenant` | `id` | `swagger` | Tenant ID for the docs. |
| `oauth2` | `client_id` | `ZAKOBO_AUTH_AC_CLIENT_ID` | OAuth client ID for Scalar. |
| `oauth2` | `server_url` | `ZAKOBO_AUTH_URL`, else `https://auth.zakobo.test` | Base URL of the Auth server. |
| `security` | `api_prefix` | `api/v4/` | Operations under this URI prefix get security requirements. |
| `security` | `tenant_only_uri_patterns` | `[]` | URI patterns that need the tenant header and no OAuth. |

Laravel merges the config one level deep.
An app that sets one key of a group replaces the whole group.
The app must set all keys of that group.

| App | `api_prefix` | `tenant.enabled` |
|---|---|---|
| Platform | `api/v4/` | `true`, with `api/v4/pa/*` as a tenant-only pattern |
| Accounting | `api/v1/` | `true` |
| Auth | `api/v1/` | `false` |

The package derives these values:

- Authorization URL: `{oauth2.server_url}/oauth/authorize`.
- Token URL: `{oauth2.server_url}/oauth/token`.
- Redirect URL: `{APP_URL}/oauth2-redirect.html`.

The package fixes these values in code:

- OAuth scheme name: `oauth2`.
- Tenant scheme name: `tenantHeader`.
- Tenant header: `X-Tenant-ID`.
- OAuth tenant query parameter: `tenant_id`.
- Document path: `/docs/api.json`.
- Exported file: `public/docs/api.json`.

## Transformers

The package always calls `preferPatchMethod()`. It also adds these transformers:

- `ScrambleSecurityRequirements` (operation): sets the security of each operation under `security.api_prefix`. An operation gets `oauth2`. With tenant support on, it also gets `tenantHeader`. A URI that matches `tenant_only_uri_patterns` gets only `tenantHeader`. An operation with empty security stays public.
- `ScrambleAuthentication` (document): adds the `oauth2` scheme and the document security requirement. With tenant support on, it also adds the `tenantHeader` scheme.
- `JsonApiErrorResponses` (document): adds the JSON:API error responses 400, 401, 403, 404, 422, and 500.
- `DocumentUuidRouteParameters` (document): documents UUID route model parameters as `string` with format `uuid`.
- `RemoveRecursiveIncludeEnumValues` (document): removes recursive relationship paths, such as `parent.parent`, from include enums.
- `DocumentFilterEnumSchemas` (document): adds the enums `*FilterField` and `*FilterParameter` for each operation with `filter[...]` parameters.

Do not copy these classes into the app. Change the config instead.

## Parameter cache

The package wraps the Scramble class `JsonApiResourceParametersExtractor`.
The wrapper keeps the `include` and sparse fieldset parameters of each JSON:API resource class.
Many routes return the same resource class. Scramble extracts the parameters once for each class.
Each route gets a deep copy, because document transformers change parameters in place.

The cache lives for one PHP process. It has no config key and no command.
A new process starts with an empty cache. `scramble:clear` does not touch it.

## Security annotations

- The PHPDoc tag `@unauthenticated` sets the operation security to an empty list. Use it only for endpoints that need no OAuth and no tenant header.
- Do not use `@unauthenticated` for endpoints that need the tenant header but no OAuth. Add a pattern to `security.tenant_only_uri_patterns`.
- After you change the auth docs, read `/docs/api.json`. A public endpoint has `"security": []`. A tenant-only endpoint has `{"tenantHeader": []}`. An authenticated tenant endpoint has `{"oauth2": [], "tenantHeader": []}`.

## Access protection

- The middleware `RestrictedDocsAccess` protects `/docs/api` and the Laravel `/docs/api.json` route. It allows the local environment. In other environments, it needs the gate `viewApiDocs`.
- The redirect page has no access middleware. It is blank.
- The web server sends the file `public/docs/api.json` before Laravel runs. That file bypasses `RestrictedDocsAccess`. Treat the document as public wherever the file is deployed.
- Access hardening changes the runtime behavior. Do it in its own story, not in a docs task.
- In tests, disable the middleware with `$this->withoutMiddleware(RestrictedDocsAccess::class)`.
- `public/docs/api.json` is a generated file. Keep it out of Git.

## Document cache

The key `scramble.cache` in `config/scramble.php` sets the cache key and the store. The default store is `file`.

- The `/docs/api.json` route reads the cache. If the cache is warm, the route returns the cached document. If the cache is cold, the route builds the document live and does not write the cache.
- Only `php artisan scramble:cache` writes the cache. It stores the document forever.
- Only `php artisan scramble:clear` removes the cache.
- Nothing removes the cache when the code changes.
- `php artisan scramble:export` ignores the cache and builds a new document.

After you change routes, controllers, form requests, resources, Data objects, or enums, do these steps:

1. Run `php artisan scramble:clear`.
2. Export the document again.
3. Run `php artisan scramble:cache` if the app uses a warm cache.

## Commands

```bash
php artisan scramble:cache     # write the document cache
php artisan scramble:clear     # remove the document cache
php artisan scramble:export --path=public/docs/api.json
php artisan scramble:analyze   # show generation problems
```

Some apps must run the export inside a tenant. The central connection cannot read the tenant tables.
If the app has the scripts `composer scramble:export` and `composer scramble:analyze`, use the scripts.

The exit code of `scramble:export` can be 0 when Scramble reports an `UnknownType` error.
Read the printed diagnostics. Do not trust only the exit code.

## Fix wrong output

Find the cause before you edit.
A missing endpoint is a route-selection problem first. Check the API path and the exclusions.
A wrong field usually comes from missing types. Check the resource model, the helper return types, dynamic validation, casts, and array shapes.

Use the smallest fix that is true. Take the steps in this order:

1. Improve the typing. Use `@mixin`, `@property`, `@property-read`, or `@throws`.
2. Add a narrow attribute for information that only the docs need. Parameter attributes merge with inference. Do not set `infer: false` to add text. Do not repeat an inferred type.
3. Extend Scramble for a reusable app convention. Use the extension point that is closest to the missing information. First check what Scramble PRO and this package already do.

Do not replace a whole inferred schema to fix one field.
If normal code gets wrong docs, suspect a Scramble bug. Search the Scramble issues. Report the reproducer, the generated fragment, the expected fragment, and the versions.
If a contract cannot be documented in a true way, name the endpoint and the field. Stop and continue with the rest.

## Test docs changes

- Run the Scramble feature tests of the app. Run `php artisan scramble:clear` first.
- When you add or change an endpoint, add contract assertions for the new paths, the request body refs, and the filter, sort, and include enums. Do not weaken the existing assertions.
- Export the document and read the changed paths with `jq`. Do not rely on the UI alone.
- Check the other endpoints that use a changed resource, rule, Data object, or extension.

## Pitfalls

- Old document: a warm cache or an old `public/docs/api.json` shows the old API. Clear the cache and export again.
- Empty or 404 `/docs/api` page: `public/docs/api.json` is missing. Export the document.
- A manual document transformer for something that Scramble infers. Fix the input of the inference instead.
- Edits to the exported file by hand.
- A commit of `public/docs/api.json`.
