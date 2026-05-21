<?php

declare(strict_types=1);

return [
    'enabled' => true,

    'scramble' => [
        'auto_configure' => true,
        'prefer_patch_method' => true,
        'ui_path' => '/docs/api',
        'document_path' => '/docs/api.json',
    ],

    'cache' => [
        /*
         * When enabled, the generated OpenAPI document is cached to a JSON file.
         * The cache is invalidated automatically when any watched file changes.
         * Recommended for local development and any environment where the docs
         * UI is rendered on demand. Disabled by default so test and CI runs
         * always regenerate fresh output.
         */
        'enabled' => env('SCRAMBLE_OPENAPI_CACHE_ENABLED', false), // @phpstan-ignore larastan.noEnvCallsOutsideOfConfig

        'path' => storage_path('framework/cache/scramble-openapi.json'),

        /*
         * Files and directories whose modification times invalidate the cache.
         * Each entry may be an absolute path to a file or a directory. PHP files
         * under directories are walked recursively.
         */
        'watched_paths' => [
            base_path('app'),
            base_path('routes'),
            base_path('config/scramble.php'),
            base_path('config/scramble-openapi.php'),
            base_path('bootstrap/providers.php'),
        ],
    ],

    'swagger_ui' => [
        'enabled' => true,
        'path' => '/docs/swagger',
        'spec_path' => '/docs/api.json',
        'auth_bootstrap_path' => null,
        'oauth_redirect_path' => '/oauth2-redirect.html',
        'legacy_oauth_redirect_path' => '/docs/swagger/oauth2-redirect',
        'oauth_scheme' => 'oauth2',
        'swagger_ui_dist_version' => '5.20.1',
    ],

    'tenant' => [
        'enabled' => false,
        'id' => 'swagger',
        'scheme' => 'tenantHeader',
        'header_name' => 'X-Tenant-ID',
        'oauth_parameter' => 'tenant_id',
    ],

    'oauth2' => [
        'client_id' => null,
        'authorization_url' => 'https://auth.zakobo.test/oauth/authorize',
        'token_url' => 'https://auth.zakobo.test/oauth/token',
        'redirect_url' => null,
        'scopes' => [],
    ],

    'security' => [
        'api_prefix' => 'api/v4/',
        'tenant_only_uri_patterns' => [
            //
        ],
    ],
];
