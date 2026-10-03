// @vitest-environment node
import { afterEach, describe, expect, it, vi } from "vitest"
import {
  jsonBody,
  SESSION_TOKEN,
  SYMFONY,
  symfonyAnswers,
} from "@tests/symfony-api"
import { PATCH } from "./route"
import { PATCH as PATCH_STATUS } from "./status/route"

vi.mock("server-only", () => ({}))

vi.mock("next/headers", () => ({
  cookies: async () => ({ get: () => ({ value: SESSION_TOKEN }) }),
}))

const ARTICLE_ID = "11111111-1111-4111-8111-111111111111"

function context(id: string) {
  return { params: Promise.resolve({ id }) }
}

function patchRequest(path: string, body: unknown) {
  return new Request(`http://localhost${path}`, {
    method: "PATCH",
    body: JSON.stringify(body),
  })
}

afterEach(() => {
  vi.unstubAllGlobals()
})

describe("PATCH /api/articles/[id]", () => {
  it("sends the article id in the path and only the fields given, trimmed", async () => {
    const requests = symfonyAnswers(200)

    await PATCH(
      patchRequest(`/api/articles/${ARTICLE_ID}`, {
        title: "  New title  ",
        author: "someone-else",
      }),
      context(ARTICLE_ID)
    )

    expect(requests).toHaveLength(1)
    expect(requests[0]).toMatchObject({
      url: `${SYMFONY}/api/editor/articles/${ARTICLE_ID}`,
      method: "PATCH",
      authorization: `Bearer ${SESSION_TOKEN}`,
    })
    expect(jsonBody(requests[0])).toEqual({ title: "New title" })
  })

  it("refuses a blank title, without asking the backend", async () => {
    const requests = symfonyAnswers(200)

    const response = await PATCH(
      patchRequest(`/api/articles/${ARTICLE_ID}`, { title: "   " }),
      context(ARTICLE_ID)
    )

    expect(response.status).toBe(400)
    expect(requests).toHaveLength(0)
  })

  it("refuses an id that is not a backend identifier, without asking the backend", async () => {
    const requests = symfonyAnswers(200)

    const response = await PATCH(
      patchRequest("/api/articles/nope", { title: "New title" }),
      context("nope")
    )

    expect(response.status).toBe(404)
    expect(requests).toHaveLength(0)
  })
})

describe("PATCH /api/articles/[id]/status", () => {
  it("sends the status asked for", async () => {
    const requests = symfonyAnswers(200)

    await PATCH_STATUS(
      patchRequest(`/api/articles/${ARTICLE_ID}/status`, {
        status: "published",
      }),
      context(ARTICLE_ID)
    )

    expect(requests[0]).toMatchObject({
      url: `${SYMFONY}/api/editor/articles/${ARTICLE_ID}/status`,
      method: "PATCH",
    })
    expect(jsonBody(requests[0])).toEqual({ status: "published" })
  })

  it("refuses a status the backend does not know, without asking it", async () => {
    const requests = symfonyAnswers(200)

    const response = await PATCH_STATUS(
      patchRequest(`/api/articles/${ARTICLE_ID}/status`, { status: "online" }),
      context(ARTICLE_ID)
    )

    expect(response.status).toBe(400)
    expect(requests).toHaveLength(0)
  })
})
