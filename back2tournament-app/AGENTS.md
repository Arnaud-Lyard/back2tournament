<!-- BEGIN:nextjs-agent-rules -->

# This is NOT the Next.js you know

This version has breaking changes — APIs, conventions, and file structure may all differ from your training data. Read the relevant guide in `node_modules/next/dist/docs/` (resolved from this file's directory; in monorepos the `next` package may not be visible from the repo root) before writing any code. Heed deprecation notices.

This block is written and re-added by `next dev` — verify at `node_modules/next/dist/server/lib/generate-agent-files.js`. Removing it from a diff only re-creates the uncommitted change; committing it with your work keeps the tree clean.

<!-- END:nextjs-agent-rules -->

# Comments

No comments: names, types and tests say what the code does. Only what a tool reads
stays: `// @vitest-environment` directives, the comment oxlint's `no-empty` wants in an
otherwise empty `catch`, and the files Orval generates in `src/libs/api/generated/`,
whose doc comments are the API's OpenAPI descriptions.

# Calling the Symfony API

The server talks to Symfony through the functions Orval generates from the API's
OpenAPI document (`orval.config.ts`): one file per OpenAPI tag in
`src/libs/api/generated/`, the schemas in `endpoints.schemas.ts`. Never edit them:
regenerate with `npm run api:generate`, which reads `OPENAPI_SCHEMA_URL` (the dev
API's `/api/doc.json` by default), and commit the result.

- Each function answers `{ data, status, headers }`, typed per status code: narrow
  on `status` to read `data`.
- Pass `await withSession()` (`src/libs/api/session.ts`) to call as the signed-in
  user; omit it for an anonymous call (sign-in, registration, public lists built
  outside a request).
- A route handler relays the answer with `relayApiResult()`, a server component
  loads it with `loadApiResult()`.
- The functions run on the server only: `symfonyFetch`, the mutator that adds
  `SYMFONY_API_URL`, imports `server-only`. Client code imports types and enums
  from `endpoints.schemas.ts` alone.
- Tests stub `fetch` with `symfonyAnswers()` (`tests/symfony-api.ts`) and assert the
  request Symfony receives: URL, verb, bearer token and body.
