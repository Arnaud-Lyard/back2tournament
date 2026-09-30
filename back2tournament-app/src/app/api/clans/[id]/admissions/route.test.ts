import { afterEach, describe, expect, it, vi } from "vitest"
import { POST } from "./route"

vi.mock("server-only", () => ({}))

const backendPost = vi.fn()

vi.mock("@/libs/api/client", () => ({
  getServerApiClient: async () => ({ POST: backendPost }),
}))

const CLAN_ID = "11111111-1111-4111-8111-111111111111"
const PLAYER_ID = "22222222-2222-4222-8222-222222222222"

function context(id: string) {
  return { params: Promise.resolve({ id }) }
}

function admission(body: unknown) {
  return new Request(`http://localhost/api/clans/${CLAN_ID}/admissions`, {
    method: "POST",
    body: JSON.stringify(body),
  })
}

afterEach(() => {
  backendPost.mockReset()
})

describe("POST /api/clans/[id]/admissions", () => {
  it("admits the player named in the body into the clan named in the path", async () => {
    backendPost.mockResolvedValue({
      data: { status: "active" },
      response: new Response(null),
    })

    await POST(admission({ player: PLAYER_ID }), context(CLAN_ID))

    expect(backendPost).toHaveBeenCalledOnce()
    const [path, options] = backendPost.mock.calls[0]
    expect(path).toBe("/api/user/clans/{id}/admissions")
    expect(options.params.path).toEqual({ id: CLAN_ID })
    expect(options.body).toEqual({ player: PLAYER_ID })
  })

  it("refuses a player that is not a backend identifier, without asking the backend", async () => {
    const response = await POST(
      admission({ player: "someone" }),
      context(CLAN_ID)
    )

    expect(response.status).toBe(400)
    expect(backendPost).not.toHaveBeenCalled()
  })

  it("refuses a clan id that is not a backend identifier, without asking the backend", async () => {
    const response = await POST(
      admission({ player: PLAYER_ID }),
      context("nope")
    )

    expect(response.status).toBe(404)
    expect(backendPost).not.toHaveBeenCalled()
  })
})
