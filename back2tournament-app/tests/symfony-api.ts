import { vi } from "vitest"

export const SYMFONY = "https://localhost"

export const SESSION_TOKEN = "session-token"

export interface SymfonyRequest {
  url: string
  method: string
  authorization: string | null
  body: RequestInit["body"]
}

export function symfonyAnswers(status: number, body: unknown = {}) {
  const requests: SymfonyRequest[] = []

  vi.stubGlobal(
    "fetch",
    vi.fn(async (url: string, init: RequestInit = {}) => {
      requests.push({
        url,
        method: init.method ?? "GET",
        authorization: new Headers(
          init.headers as Record<string, string> | undefined
        ).get("Authorization"),
        body: init.body,
      })
      return Response.json(body, { status })
    })
  )

  return requests
}

export function formBody(request: SymfonyRequest | undefined): FormData {
  const body = request?.body
  if (!(body instanceof FormData)) throw new TypeError("not a multipart form")
  return body
}

export function jsonBody(request: SymfonyRequest | undefined): unknown {
  return JSON.parse(String(request?.body))
}
