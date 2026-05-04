<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ config('scramble-openapi.title', 'API Docs') }}</title>
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

        .zakobo-swagger-toolbar {
            padding-top: 36px;
        }

        .zakobo-swagger-toolbar__label {
            color: #111827;
            display: block;
            font-family: sans-serif;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .zakobo-swagger-toolbar__input {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            box-sizing: border-box;
            color: #111827;
            font-family: sans-serif;
            font-size: 14px;
            padding: 10px 12px;
            width: 100%;
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
@include('scramble-openapi::swagger-ui-scripts')
</body>
</html>
