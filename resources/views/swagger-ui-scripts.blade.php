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

    function normalizeEndpointFilterText(value) {
        return String(value || '')
            .toLowerCase()
            .replace(/\s+/g, ' ')
            .trim();
    }

    function operationSearchText(section, operation) {
        const values = [
            section.querySelector('.opblock-tag')?.textContent,
            operation.querySelector('.opblock-summary-method')?.textContent,
            operation.querySelector('.opblock-summary-path')?.textContent,
            operation.querySelector('.opblock-summary-description')?.textContent,
        ];

        return normalizeEndpointFilterText(values.filter(Boolean).join(' '));
    }

    function applyEndpointFilter(query) {
        const needle = normalizeEndpointFilterText(query);
        const sections = document.querySelectorAll('#swagger-ui .opblock-tag-section');

        sections.forEach((section) => {
            let hasVisibleOperation = false;

            section.querySelectorAll('.opblock').forEach((operation) => {
                const matches = needle === '' || operationSearchText(section, operation).includes(needle);

                operation.style.display = matches ? '' : 'none';
                hasVisibleOperation = hasVisibleOperation || matches;
            });

            section.style.display = needle === '' || hasVisibleOperation ? '' : 'none';
        });
    }

    function ensureEndpointFilterInput(config) {
        let input = document.getElementById(config.endpointFilterId);

        if (input) {
            return input;
        }

        const schemeContainer = document.querySelector('#swagger-ui .scheme-container');

        if (!schemeContainer || !schemeContainer.parentElement) {
            return null;
        }

        const toolbar = document.createElement('div');
        const content = document.createElement('div');
        const label = document.createElement('label');

        input = document.createElement('input');

        toolbar.className = 'zakobo-swagger-toolbar';
        content.className = 'wrapper';
        label.className = 'zakobo-swagger-toolbar__label';
        label.htmlFor = config.endpointFilterId;
        label.textContent = 'Filter endpoints';

        input.id = config.endpointFilterId;
        input.className = 'zakobo-swagger-toolbar__input';
        input.type = 'search';
        input.placeholder = 'Filter by method, path, summary or tag. Example: cms, /v4/pa, products';
        input.autocomplete = 'off';

        content.append(label, input);
        toolbar.append(content);
        schemeContainer.parentElement.insertBefore(toolbar, schemeContainer.nextSibling);

        return input;
    }

    function bootEndpointFilter(config) {
        const container = document.getElementById('swagger-ui');
        let input = document.getElementById(config.endpointFilterId);

        if (!container) {
            return;
        }

        let scheduled = false;
        const scheduleFilter = () => {
            if (scheduled) {
                return;
            }

            scheduled = true;

            window.requestAnimationFrame(() => {
                scheduled = false;
                input = input || ensureEndpointFilterInput(config);
                applyEndpointFilter(input?.value || '');
                bindInput();
            });
        };

        const bindInput = () => {
            input = input || ensureEndpointFilterInput(config);

            if (!input || input.dataset.endpointFilterBound === 'true') {
                return;
            }

            input.dataset.endpointFilterBound = 'true';
            input.addEventListener('input', scheduleFilter);
        };

        new MutationObserver(scheduleFilter).observe(container, {
            childList: true,
            subtree: true,
        });

        bindInput();
        scheduleFilter();
    }

    function bootSwaggerUi(spec, oauth, config) {
        window.ui = SwaggerUIBundle({
            spec,
            dom_id: '#swagger-ui',
            deepLinking: true,
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
            endpointFilterId: 'swagger-endpoint-filter',
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
        bootEndpointFilter(config);
    };
</script>
