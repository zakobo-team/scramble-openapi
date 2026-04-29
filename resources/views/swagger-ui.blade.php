<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ config('scramble-sso-auth-driver.title', 'API Docs') }}</title>
    <link rel="stylesheet" href="{{ 'https://unpkg.com/swagger-ui-dist@'.$swaggerUiDistVersion.'/swagger-ui.css' }}">
    <style>
        body {
            margin: 0;
        }

        .zakobo-swagger-auth {
            display: flex;
            justify-content: flex-end;
            padding: 16px 40px 0;
        }

        .zakobo-swagger-auth__button {
            background: #111827;
            border: 0;
            border-radius: 4px;
            color: #fff;
            cursor: pointer;
            font-family: sans-serif;
            font-size: 14px;
            font-weight: 700;
            padding: 10px 16px;
        }

        .zakobo-swagger-auth__button[disabled] {
            cursor: wait;
            opacity: .6;
        }

        .swagger-ui .scheme-container .auth-wrapper {
            display: none;
        }
    </style>
</head>
<body>
<div class="zakobo-swagger-auth">
    <button id="swagger-auth-button" class="zakobo-swagger-auth__button" type="button">
        Authentication
    </button>
</div>
<div id="swagger-ui"></div>
<script src="{{ 'https://unpkg.com/swagger-ui-dist@'.$swaggerUiDistVersion.'/swagger-ui-bundle.js' }}"></script>
@include('scramble-sso-auth-driver::swagger-ui-scripts')
</body>
</html>
