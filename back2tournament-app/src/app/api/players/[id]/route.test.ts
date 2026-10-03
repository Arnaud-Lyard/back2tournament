// @vitest-environment node
import { afterEach, describe, expect, it, vi } from "vitest"
import {
  jsonBody,
  SESSION_TOKEN,
  SYMFONY,
  symfonyAnswers,
} from "@tests/symfony-api"
import { DELETE, PATCH } from "./route"

vi.mock("server-only", () => ({}))

vi.mock("next/headers", () => ({
  cookies: async () => ({ get: () => ({ value: SESSION_TOKEN }) }),
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
  vi.unstubAllGlobals()
})

describe("PATCH /api/players/[id]", () => {
  it("sends the profile id in the path and the battletag alone in the body", async () => {
    const requests = symfonyAnswers(200)

    await PATCH(
      patchRequest({ battletag: "  PlayerTwo#5678  ", game: "ignored" }),
      context(PLAYER_ID)
    )

    expect(requests).toHaveLength(1)
    expect(requests[0]).toMatchObject({
      url: `${SYMFONY}/api/user/players/${PLAYER_ID}`,
      method: "PATCH",
      authorization: `Bearer ${SESSION_TOKEN}`,
    })
    expect(jsonBody(requests[0])).toEqual({ battletag: "PlayerTwo#5678" })
  })

  it("refuses an id that is not a backend identifier, without asking the backend", async () => {
    const requests = symfonyAnswers(200)

    const response = await PATCH(
      patchRequest({ battletag: "PlayerTwo#5678" }),
      context("not-an-uuid")
    )

    expect(response.status).toBe(404)
    expect(requests).toHaveLength(0)
  })

  it("refuses an empty battletag, without asking the backend", async () => {
    const requests = symfonyAnswers(200)

    const response = await PATCH(
      patchRequest({ battletag: "   " }),
      context(PLAYER_ID)
    )

    expect(response.status).toBe(400)
    expect(requests).toHaveLength(0)
  })
})

describe("DELETE /api/players/[id]", () => {
  it("sends the profile id in the path", async () => {
    const requests = symfonyAnswers(200)

    await DELETE(
      new Request(`http://localhost/api/players/${PLAYER_ID}`, {
        method: "DELETE",
      }),
      context(PLAYER_ID)
    )

    expect(requests).toHaveLength(1)
    expect(requests[0]).toMatchObject({
      url: `${SYMFONY}/api/user/players/${PLAYER_ID}`,
      method: "DELETE",
      authorization: `Bearer ${SESSION_TOKEN}`,
    })
  })

  it("refuses an id that is not a backend identifier, without asking the backend", async () => {
    const requests = symfonyAnswers(200)

    const response = await DELETE(
      new Request("http://localhost/api/players/nope", { method: "DELETE" }),
      context("nope")
    )

    expect(response.status).toBe(404)
    expect(requests).toHaveLength(0)
  })
})
