import { afterEach, describe, expect, it, vi } from "vitest"
import { PATCH } from "./route"

vi.mock("server-only", () => ({}))

const backendPatch = vi.fn()

vi.mock("@/libs/api/client", () => ({
  getServerApiClient: async () => ({ PATCH: backendPatch }),
}))

const FIGHT_ID = "11111111-1111-4111-8111-111111111111"
const ONE = "22222222-2222-4222-8222-222222222222"
const TWO = "33333333-3333-4333-8333-333333333333"

function context(id: string) {
  return { params: Promise.resolve({ id }) }
}

function patchRequest(body: unknown) {
  return new Request(`http://localhost/api/fights/${FIGHT_ID}/status`, {
    method: "PATCH",
    body: JSON.stringify(body),
  })
}

afterEach(() => {
  backendPatch.mockReset()
})

describe("PATCH /api/fights/[id]/status", () => {
  it("sends the scores an administrator imposes, keyed by side", async () => {
    backendPatch.mockResolvedValue({ data: {}, response: new Response(null) })

    await PATCH(
      patchRequest({ status: "finished", scores: { [ONE]: 3, [TWO]: 1 } }),
      context(FIGHT_ID)
    )

    const [path, options] = backendPatch.mock.calls[0]
    expect(path).toBe("/api/fights/{id}/status")
    expect(options.params.path).toEqual({ id: FIGHT_ID })
    expect(options.body).toEqual({
      status: "finished",
      scores: { [ONE]: 3, [TWO]: 1 },
    })
  })

  it("sets a declaration aside with the status alone", async () => {
    backendPatch.mockResolvedValue({ data: {}, response: new Response(null) })

    await PATCH(
      patchRequest({ status: "pending", scores: { [ONE]: 3 } }),
      context(FIGHT_ID)
    )

    expect(backendPatch.mock.calls[0][1].body).toEqual({ status: "pending" })
  })

  it("refuses a negative score, without asking the backend", async () => {
    const response = await PATCH(
      patchRequest({ status: "finished", scores: { [ONE]: -1, [TWO]: 2 } }),
      context(FIGHT_ID)
    )

    expect(response.status).toBe(400)
    expect(backendPatch).not.toHaveBeenCalled()
  })

  it("refuses a status the backend does not know", async () => {
    const response = await PATCH(
      patchRequest({ status: "reporting" }),
      context(FIGHT_ID)
    )

    expect(response.status).toBe(400)
    expect(backendPatch).not.toHaveBeenCalled()
  })
})
