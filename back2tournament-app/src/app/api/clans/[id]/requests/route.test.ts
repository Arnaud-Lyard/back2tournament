// @vitest-environment node
import { afterEach, describe, expect, it, vi } from "vitest"
import { SESSION_TOKEN, SYMFONY, symfonyAnswers } from "@tests/symfony-api"
import { POST } from "./route"

vi.mock("server-only", () => ({}))

vi.mock("next/headers", () => ({
  cookies: async () => ({ get: () => ({ value: SESSION_TOKEN }) }),
}))

const CLAN_ID = "11111111-1111-4111-8111-111111111111"

function context(id: string) {
  return { params: Promise.resolve({ id }) }
}

afterEach(() => {
  vi.unstubAllGlobals()
})

describe("POST /api/clans/[id]/requests", () => {
  it("asks to join the clan named in the path", async () => {
    const requests = symfonyAnswers(200, { status: "requested" })

    const response = await POST(
      new Request(`http://localhost/api/clans/${CLAN_ID}/requests`, {
        method: "POST",
      }),
      context(CLAN_ID)
    )

    expect(response.status).toBe(200)
    expect(await response.json()).toEqual({ status: "requested" })
    expect(requests).toHaveLength(1)
    expect(requests[0]).toMatchObject({
      url: `${SYMFONY}/api/user/clans/${CLAN_ID}/requests`,
      method: "POST",
      authorization: `Bearer ${SESSION_TOKEN}`,
    })
  })

  it("refuses an id that is not a backend identifier, without asking the backend", async () => {
    const requests = symfonyAnswers(200)

    const response = await POST(
      new Request("http://localhost/api/clans/nope/requests", {
        method: "POST",
      }),
      context("nope")
    )

    expect(response.status).toBe(404)
    expect(requests).toHaveLength(0)
  })
})
