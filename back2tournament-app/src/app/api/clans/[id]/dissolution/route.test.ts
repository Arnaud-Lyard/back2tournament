import { afterEach, describe, expect, it, vi } from "vitest"
import { POST } from "./route"

vi.mock("server-only", () => ({}))

const backendPost = vi.fn()

vi.mock("@/libs/api/client", () => ({
  getServerApiClient: async () => ({ POST: backendPost }),
}))

const CLAN_ID = "11111111-1111-4111-8111-111111111111"

function context(id: string) {
  return { params: Promise.resolve({ id }) }
}

function dissolution(body: unknown) {
  return new Request(`http://localhost/api/clans/${CLAN_ID}/dissolution`, {
    method: "POST",
    body: JSON.stringify(body),
  })
}

afterEach(() => {
  backendPost.mockReset()
})

describe("POST /api/clans/[id]/dissolution", () => {
  it("dissolves the clan named in the path with the leader's password", async () => {
    backendPost.mockResolvedValue({
      data: { dissolvedAt: "2026-10-01T12:00:00+00:00" },
      response: new Response(null),
    })

    await POST(dissolution({ password: "Secret1!" }), context(CLAN_ID))

    expect(backendPost).toHaveBeenCalledOnce()
    const [path, options] = backendPost.mock.calls[0]
    expect(path).toBe("/api/user/clans/{id}/dissolution")
    expect(options.params.path).toEqual({ id: CLAN_ID })
    expect(options.body).toEqual({ password: "Secret1!" })
  })

  it("refuses an empty password, without asking the backend", async () => {
    const response = await POST(dissolution({ password: "" }), context(CLAN_ID))

    expect(response.status).toBe(400)
    expect(backendPost).not.toHaveBeenCalled()
  })

  it("refuses a clan id that is not a backend identifier, without asking the backend", async () => {
    const response = await POST(
      dissolution({ password: "Secret1!" }),
      context("nope")
    )

    expect(response.status).toBe(404)
    expect(backendPost).not.toHaveBeenCalled()
  })
})
