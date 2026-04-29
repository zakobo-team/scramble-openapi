<!doctype html>
<html lang="en">
<head>
    <title>{{ config('scramble.info.title', 'API Docs') }}</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://unpkg.com/@stoplight/elements/web-components.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/@stoplight/elements/styles.min.css">
</head>
<body>
<elements-api
    apiDescriptionDocument='@json($spec)'
    router="hash"
    layout="sidebar"
></elements-api>
<script>
    const tenantEnabled = @json($tenantEnabled);
    const tenantId = @json($tenantId);
    const tenantHeaderName = @json($tenantHeaderName);
    const nativeFetch = window.fetch.bind(window);

    window.fetch = (input, init = {}) => {
        const headers = new Headers(init.headers || {});

        if (tenantEnabled) {
            headers.set(tenantHeaderName, tenantId);
        }

        return nativeFetch(input, {
            ...init,
            headers,
        });
    };
</script>
</body>
</html>
