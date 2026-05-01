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
