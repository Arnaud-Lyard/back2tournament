// @vitest-environment node
import { afterEach, describe, expect, it, vi } from "vitest"
import {
  jsonBody,
  SESSION_TOKEN,
  SYMFONY,
  symfonyAnswers,
} from "@tests/symfony-api"
import { POST } from "./route"

vi.mock("server-only", () => ({}))

vi.mock("next/headers", () => ({
  cookies: async () => ({ get: () => ({ value: SESSION_TOKEN }) }),
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
  vi.unstubAllGlobals()
})

describe("POST /api/clans/[id]/admissions", () => {
  it("admits the player named in the body into the clan named in the path", async () => {
    const requests = symfonyAnswers(200, { status: "active" })

    await POST(admission({ player: PLAYER_ID }), context(CLAN_ID))

    expect(requests).toHaveLength(1)
    expect(requests[0]).toMatchObject({
      url: `${SYMFONY}/api/user/clans/${CLAN_ID}/admissions`,
      method: "POST",
      authorization: `Bearer ${SESSION_TOKEN}`,
    })
    expect(jsonBody(requests[0])).toEqual({ player: PLAYER_ID })
  })

  it("refuses a player that is not a backend identifier, without asking the backend", async () => {
    const requests = symfonyAnswers(200)

    const response = await POST(
      admission({ player: "someone" }),
      context(CLAN_ID)
    )

    expect(response.status).toBe(400)
    expect(requests).toHaveLength(0)
  })

  it("refuses a clan id that is not a backend identifier, without asking the backend", async () => {
    const requests = symfonyAnswers(200)

    const response = await POST(
      admission({ player: PLAYER_ID }),
      context("nope")
    )

    expect(response.status).toBe(404)
    expect(requests).toHaveLength(0)
  })
})
