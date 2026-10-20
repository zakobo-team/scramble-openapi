<?php

declare(strict_types=1);

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
