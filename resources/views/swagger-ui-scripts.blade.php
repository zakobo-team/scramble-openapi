<script>
    async function loadJson(url, headers) {
        const response = await fetch(url, { headers });

        if (!response.ok) {
            throw new Error(`Unable to load ${url}.`);
        }

        return response.json();
    }

    function applyOAuthMetadata(spec, oauth, config) {
        const authorizationCode = spec.components?.securitySchemes?.[config.oauthScheme]?.flows?.authorizationCode;

        if (!authorizationCode) {
            return spec;
        }

        authorizationCode.authorizationUrl = oauth.authorization_url || authorizationCode.authorizationUrl;
        authorizationCode.tokenUrl = oauth.token_url || authorizationCode.tokenUrl;

        return spec;
    }

    async function resolveOAuthMetadata(config, headers) {
        if (!config.authBootstrapUrl) {
            return config.oauth;
        }

        const authBootstrapUrl = new URL(config.authBootstrapUrl);

        authBootstrapUrl.searchParams.set('redirect_uri', config.oauth2RedirectUrl);

        const { meta } = await loadJson(authBootstrapUrl, headers);

        return {
            ...config.oauth,
            ...meta,
        };
    }

    function assertOAuthMetadata(oauth) {
        if (!oauth.client_id) {
            throw new Error('Missing OAuth client_id. Configure auth_bootstrap_path or oauth2.client_id.');
        }
    }

    function base64UrlEncode(value) {
        const bytes = value instanceof ArrayBuffer ? new Uint8Array(value) : value;
        const binary = Array.from(bytes, (byte) => String.fromCharCode(byte)).join('');

        return btoa(binary)
            .replace(/\+/g, '-')
            .replace(/\//g, '_')
            .replace(/=+$/, '');
    }

    function generateCodeVerifier() {
        const bytes = new Uint8Array(32);

        crypto.getRandomValues(bytes);

        return base64UrlEncode(bytes);
    }

    async function createCodeChallenge(codeVerifier) {
        const digest = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(codeVerifier));

        return base64UrlEncode(digest);
    }

    function getSecuritySchema(name) {
        const definitions = window.ui.authSelectors.definitionsToAuthorize();
        const definition = definitions?.find((item) => item.has(name));

        return definition?.get(name);
    }

    function isAuthorized(config) {
        return Boolean(window.ui.authSelectors.authorized().get(config.oauthScheme));
    }

    function updateAuthenticationButton(config) {
        const button = document.getElementById(config.authButtonId);

        if (!button) {
            return;
        }

        button.textContent = isAuthorized(config) ? 'Logout' : 'Authentication';
    }

    function openOAuthPopup(url, payload, popup) {
        if (popup && !popup.closed) {
            window.swaggerUIRedirectOauth2 = payload;
            popup.location.href = url;

            return;
        }

        window.ui.authActions.authPopup(url, payload);
    }

    async function authenticate(oauth, config, popup) {
        const schema = getSecuritySchema(config.oauthScheme);

        if (!schema) {
            throw new Error(`Unable to find ${config.oauthScheme} security scheme.`);
        }

        const auth = {
            schema,
            scopes: config.scopes,
            name: config.oauthScheme,
            clientId: oauth.client_id,
            clientSecret: '',
            username: '',
            password: '',
            passwordType: 'basic',
        };
        const codeVerifier = generateCodeVerifier();
        const query = new URLSearchParams({
            response_type: 'code',
            client_id: auth.clientId,
            redirect_uri: config.oauth2RedirectUrl,
            state: btoa(String(new Date())),
            code_challenge: await createCodeChallenge(codeVerifier),
            code_challenge_method: 'S256',
        });
        const scopes = Array.isArray(config.scopes) ? config.scopes : [];

        if (config.tenantEnabled && config.oauthTenantParameter) {
            query.set(config.oauthTenantParameter, config.tenantId);
        }

        if (scopes.length > 0) {
            query.set('scope', scopes.join(' '));
        }

        auth.codeVerifier = codeVerifier;

        const url = [
            schema.get('authorizationUrl'),
            query.toString(),
        ].join(schema.get('authorizationUrl').includes('?') ? '&' : '?');
        const payload = {
            auth,
            state: query.get('state'),
            redirectUrl: config.oauth2RedirectUrl,
            callback: window.ui.authActions.authorizeAccessCodeWithFormParams,
            errCb: window.ui.errActions.newAuthErr,
        };

        window.ui.errActions.clear({ authId: config.oauthScheme, type: 'auth', source: 'auth' });
        openOAuthPopup(url, payload, popup);
    }

    function bootAuthenticationButton(oauth, config) {
        const button = document.getElementById(config.authButtonId);

        if (!button) {
            return;
        }

        updateAuthenticationButton(config);

        if (typeof window.ui.getStore === 'function') {
            window.ui.getStore().subscribe(() => updateAuthenticationButton(config));
        } else {
            window.setInterval(() => updateAuthenticationButton(config), 500);
        }

        button.addEventListener('click', async () => {
            button.disabled = true;

            try {
                if (isAuthorized(config)) {
                    window.ui.authActions.logoutWithPersistOption([config.oauthScheme]);
                    delete window.swaggerUIRedirectOauth2;

                    return;
                }

                await authenticate(oauth, config, window.open('', 'swagger-oauth2'));
            } finally {
                button.disabled = false;
                updateAuthenticationButton(config);
            }
        });
    }

    function bootSwaggerUi(spec, oauth, config) {
        window.ui = SwaggerUIBundle({
            spec,
            dom_id: '#swagger-ui',
            deepLinking: true,
            filter: true,
            persistAuthorization: true,
            oauth2RedirectUrl: config.oauth2RedirectUrl,
            requestInterceptor: (request) => {
                request.headers = request.headers || {};

                if (config.tenantEnabled) {
                    request.headers[config.tenantHeaderName] = request.headers[config.tenantHeaderName] || config.tenantId;
                }

                return request;
            },
        });

        if (config.tenantEnabled) {
            window.ui.preauthorizeApiKey(config.tenantScheme, config.tenantId);
        }

        window.ui.initOAuth({
            usePkceWithAuthorizationCodeGrant: true,
            useBasicAuthenticationWithAccessCodeGrant: false,
            clientId: oauth.client_id,
            scopes: config.scopes,
            additionalQueryStringParams: config.tenantEnabled && config.oauthTenantParameter
                ? { [config.oauthTenantParameter]: config.tenantId }
                : {},
        });
    }

    window.onload = async () => {
        const config = {
            specUrl: @json($specUrl),
            authBootstrapUrl: @json($authBootstrapUrl),
            oauth2RedirectUrl: @json($oauth2RedirectUrl),
            scopes: @json($oauthScopes),
            oauth: @json($oauthMetadata),
            oauthScheme: @json($oauthScheme),
            tenantEnabled: @json($tenantEnabled),
            tenantId: @json($tenantId),
            tenantScheme: @json($tenantScheme),
            tenantHeaderName: @json($tenantHeaderName),
            oauthTenantParameter: @json($oauthTenantParameter),
            authButtonId: 'swagger-auth-button',
        };
        const headers = {
            Accept: 'application/json',
        };
        const authBootstrapUrl = new URL(config.authBootstrapUrl);

        if (config.tenantEnabled) {
            headers[config.tenantHeaderName] = config.tenantId;
        }

        const [oauth, spec] = await Promise.all([
            resolveOAuthMetadata(config, headers),
            loadJson(config.specUrl, headers),
        ]);

        assertOAuthMetadata(oauth);
        bootSwaggerUi(applyOAuthMetadata(spec, oauth, config), oauth, config);
        bootAuthenticationButton(oauth, config);
    };
</script>
