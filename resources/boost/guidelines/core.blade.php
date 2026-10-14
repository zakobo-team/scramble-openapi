## Scramble OpenAPI

This app documents its API with Scramble and `zakobo/scramble-openapi`.
Scalar is the only docs UI.

- Activate the `scramble-openapi-development` skill for any work on API docs, OpenAPI output, docs routes, the Scalar login, or the Scramble tests.
- Prefer inference over annotation. Let Scramble read the code.
- Do not change runtime behavior only to change the docs.
- Keep `scramble.renderers.scalar.proxyUrl` set to `null`.
- Do not edit `public/docs/api.json` by hand. Export it again.
- After an API change, run `php artisan scramble:clear` before you trust the output.
