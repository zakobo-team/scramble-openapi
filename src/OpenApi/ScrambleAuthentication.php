<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\OpenApi;

use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityRequirement;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\Generator\SecuritySchemes\OAuthFlow;

class ScrambleAuthentication
{
    public const OAUTH_SCHEME = 'oauth2';

    public const TENANT_SCHEME = 'tenantHeader';

    public const TENANT_HEADER = 'X-Tenant-ID';

    public const TENANT_QUERY_PARAMETER = 'tenant_id';

    public const REDIRECT_PATH = '/oauth2-redirect.html';

    /**
     * @return array<string, mixed>
     */
    public static function scalarAuthentication(): array
    {
        $tenantId = config('scramble-openapi.tenant.id');

        $authorizationCodeFlow = [
            'x-scalar-client-id' => config('scramble-openapi.oauth2.client_id'),
            'x-usePkce' => 'SHA-256',
            'x-scalar-redirect-uri' => rtrim((string) config('app.url'), '/').self::REDIRECT_PATH,
        ];
        $preferredSchemes = [self::OAUTH_SCHEME];
        $securitySchemes = [];

        if ((bool) config('scramble-openapi.tenant.enabled')) {
            $preferredSchemes[] = self::TENANT_SCHEME;
            $securitySchemes[self::TENANT_SCHEME] = [
                'name' => self::TENANT_HEADER,
                'in' => 'header',
                'value' => $tenantId,
            ];
            $authorizationCodeFlow['x-scalar-security-query'] = [self::TENANT_QUERY_PARAMETER => $tenantId];
        }

        $securitySchemes[self::OAUTH_SCHEME] = ['flows' => ['authorizationCode' => $authorizationCodeFlow]];

        return [
            'preferredSecurityScheme' => [$preferredSchemes],
            'securitySchemes' => $securitySchemes,
        ];
    }

    public function __invoke(OpenApi $openApi): void
    {
        $serverUrl = rtrim((string) config('scramble-openapi.oauth2.server_url'), '/');

        $openApi->components->securitySchemes[self::OAUTH_SCHEME] = SecurityScheme::oauth2()
            ->flow('authorizationCode', function (OAuthFlow $flow) use ($serverUrl): void {
                $flow
                    ->authorizationUrl($serverUrl.'/oauth/authorize')
                    ->tokenUrl($serverUrl.'/oauth/token');
            });

        $security = [self::OAUTH_SCHEME => []];

        if ((bool) config('scramble-openapi.tenant.enabled', false)) {
            $openApi->components->securitySchemes[self::TENANT_SCHEME] = SecurityScheme::apiKey('header', self::TENANT_HEADER)
                ->setDescription('Tenant identifier required for tenant API requests.');

            $security[self::TENANT_SCHEME] = [];
        }

        $openApi->security[] = new SecurityRequirement($security);
    }
}
