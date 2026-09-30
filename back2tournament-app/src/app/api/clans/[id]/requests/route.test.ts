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

afterEach(() => {
  backendPost.mockReset()
})

describe("POST /api/clans/[id]/requests", () => {
  it("asks to join the clan named in the path", async () => {
    backendPost.mockResolvedValue({
      data: { status: "requested" },
      response: new Response(null),
    })

    const response = await POST(
      new Request(`http://localhost/api/clans/${CLAN_ID}/requests`, {
        method: "POST",
      }),
      context(CLAN_ID)
    )

    expect(response.status).toBe(200)
    expect(backendPost).toHaveBeenCalledOnce()
    const [path, options] = backendPost.mock.calls[0]
    expect(path).toBe("/api/user/clans/{id}/requests")
    expect(options.params.path).toEqual({ id: CLAN_ID })
  })

  it("refuses an id that is not a backend identifier, without asking the backend", async () => {
    const response = await POST(
      new Request("http://localhost/api/clans/nope/requests", {
        method: "POST",
      }),
      context("nope")
    )

    expect(response.status).toBe(404)
    expect(backendPost).not.toHaveBeenCalled()
  })
})
