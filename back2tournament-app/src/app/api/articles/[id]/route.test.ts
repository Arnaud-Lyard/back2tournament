import { afterEach, describe, expect, it, vi } from "vitest"
import { PATCH } from "./route"
import { PATCH as PATCH_STATUS } from "./status/route"

vi.mock("server-only", () => ({}))

const backendPatch = vi.fn()

vi.mock("@/libs/api/client", () => ({
  getServerApiClient: async () => ({ PATCH: backendPatch }),
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
  backendPatch.mockReset()
})

describe("PATCH /api/articles/[id]", () => {
  it("sends the article id in the path and only the fields given, trimmed", async () => {
    backendPatch.mockResolvedValue({ data: {}, response: new Response(null) })

    await PATCH(
      patchRequest(`/api/articles/${ARTICLE_ID}`, {
        title: "  New title  ",
        author: "someone-else",
      }),
      context(ARTICLE_ID)
    )

    expect(backendPatch).toHaveBeenCalledOnce()
    const [path, options] = backendPatch.mock.calls[0]
    expect(path).toBe("/api/articles/{id}")
    expect(options.params.path).toEqual({ id: ARTICLE_ID })
    expect(options.body).toEqual({ title: "New title" })
  })

  it("refuses a blank title, without asking the backend", async () => {
    const response = await PATCH(
      patchRequest(`/api/articles/${ARTICLE_ID}`, { title: "   " }),
      context(ARTICLE_ID)
    )

    expect(response.status).toBe(400)
    expect(backendPatch).not.toHaveBeenCalled()
  })

  it("refuses an id that is not a backend identifier, without asking the backend", async () => {
    const response = await PATCH(
      patchRequest("/api/articles/nope", { title: "New title" }),
      context("nope")
    )

    expect(response.status).toBe(404)
    expect(backendPatch).not.toHaveBeenCalled()
  })
})

describe("PATCH /api/articles/[id]/status", () => {
  it("sends the status asked for", async () => {
    backendPatch.mockResolvedValue({ data: {}, response: new Response(null) })

    await PATCH_STATUS(
      patchRequest(`/api/articles/${ARTICLE_ID}/status`, {
        status: "published",
      }),
      context(ARTICLE_ID)
    )

    const [path, options] = backendPatch.mock.calls[0]
    expect(path).toBe("/api/articles/{id}/status")
    expect(options.params.path).toEqual({ id: ARTICLE_ID })
    expect(options.body).toEqual({ status: "published" })
  })

  it("refuses a status the backend does not know, without asking it", async () => {
    const response = await PATCH_STATUS(
      patchRequest(`/api/articles/${ARTICLE_ID}/status`, { status: "online" }),
      context(ARTICLE_ID)
    )

    expect(response.status).toBe(400)
    expect(backendPatch).not.toHaveBeenCalled()
  })
})
