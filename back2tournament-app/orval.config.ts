import { defineConfig } from "orval"

const VERBS = new Set(["get", "post", "put", "patch", "delete"])

function operationName(operation: { operationId?: string }): string {
  const words = (operation.operationId ?? "").split("_")
  const [verb, ...rest] =
    words[1] === "api" ? [words[0], ...words.slice(2)] : words
  const name =
    rest.at(-1) === verb && VERBS.has(verb) ? rest.slice(0, -1) : rest

  return [
    verb,
    ...name.map((word) => word.charAt(0).toUpperCase() + word.slice(1)),
  ].join("")
}

export default defineConfig({
  symfony: {
    input: {
      target:
        process.env.OPENAPI_SCHEMA_URL ?? "https://localhost/api/doc.json",
    },
    output: {
      mode: "tags",
      target: "src/libs/api/generated/endpoints.ts",
      client: "fetch",
      httpClient: "fetch",
      clean: true,
      urlEncodeParameters: true,
      formatter: "oxfmt",
      override: {
        operationName,
        mutator: {
          path: "src/libs/api/symfony-fetch.ts",
          name: "symfonyFetch",
        },
        fetch: {
          includeHttpResponseReturnType: true,
        },
      },
    },
  },
})
