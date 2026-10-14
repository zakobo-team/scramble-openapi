## Scramble OpenAPI

This app documents its API with Scramble and `zakobo/scramble-openapi`.
Scalar is the only docs UI.

- Activate the `scramble-openapi-development` skill for any work on API docs, OpenAPI output, docs routes, the Scalar login, or the Scramble tests.
- Prefer inference over annotation. Let Scramble read the code.
- Do not change runtime behavior only to change the docs.
- Give every API operation `#[Group('Name')]` on the controller and `#[Endpoint(title: '...')]` on the action. The name must be in the allowed group list of the app. A contract test enforces this. This rule overrides the native `scramble-development` skill on endpoint titles. Do not add descriptions, examples, or parameter text unless the task asks for them.
- Keep `scramble.renderers.scalar.proxyUrl` set to `null`.
- Do not edit `public/docs/api.json` by hand. Export it again.
- After an API change, run `php artisan scramble:clear` before you trust the output.
