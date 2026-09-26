import { afterEach, describe, expect, it, vi } from "vitest"
import { DELETE, PATCH } from "./route"

vi.mock("server-only", () => ({}))

const backendPatch = vi.fn()
const backendDelete = vi.fn()

vi.mock("@/libs/api/client", () => ({
  getServerApiClient: async () => ({
    PATCH: backendPatch,
    DELETE: backendDelete,
  }),
}))

const PLAYER_ID = "11111111-1111-4111-8111-111111111111"

function context(id: string) {
  return { params: Promise.resolve({ id }) }
}

function patchRequest(body: unknown) {
  return new Request(`http://localhost/api/players/${PLAYER_ID}`, {
    method: "PATCH",
    body: JSON.stringify(body),
  })
}

afterEach(() => {
  backendPatch.mockReset()
  backendDelete.mockReset()
})

describe("PATCH /api/players/[id]", () => {
  it("sends the profile id in the path and the battletag alone in the body", async () => {
    backendPatch.mockResolvedValue({ data: {}, response: new Response(null) })

    await PATCH(
      patchRequest({ battletag: "  PlayerTwo#5678  ", game: "ignored" }),
      context(PLAYER_ID)
    )

    expect(backendPatch).toHaveBeenCalledOnce()
    const [path, options] = backendPatch.mock.calls[0]
    expect(path).toBe("/api/players/{id}")
    expect(options.params.path).toEqual({ id: PLAYER_ID })
    expect(options.body).toEqual({ battletag: "PlayerTwo#5678" })
  })

  it("refuses an id that is not a backend identifier, without asking the backend", async () => {
    const response = await PATCH(
      patchRequest({ battletag: "PlayerTwo#5678" }),
      context("not-an-uuid")
    )

    expect(response.status).toBe(404)
    expect(backendPatch).not.toHaveBeenCalled()
  })

  it("refuses an empty battletag, without asking the backend", async () => {
    const response = await PATCH(
      patchRequest({ battletag: "   " }),
      context(PLAYER_ID)
    )

    expect(response.status).toBe(400)
    expect(backendPatch).not.toHaveBeenCalled()
  })
})

describe("DELETE /api/players/[id]", () => {
  it("sends the profile id in the path", async () => {
    backendDelete.mockResolvedValue({ data: {}, response: new Response(null) })

    await DELETE(
      new Request(`http://localhost/api/players/${PLAYER_ID}`, {
        method: "DELETE",
      }),
      context(PLAYER_ID)
    )

    expect(backendDelete).toHaveBeenCalledOnce()
    const [path, options] = backendDelete.mock.calls[0]
    expect(path).toBe("/api/players/{id}")
    expect(options.params.path).toEqual({ id: PLAYER_ID })
  })

  it("refuses an id that is not a backend identifier, without asking the backend", async () => {
    const response = await DELETE(
      new Request("http://localhost/api/players/nope", { method: "DELETE" }),
      context("nope")
    )

    expect(response.status).toBe(404)
    expect(backendDelete).not.toHaveBeenCalled()
  })
})
