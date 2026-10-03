// @vitest-environment node
import { afterEach, describe, expect, it, vi } from "vitest"
import {
  jsonBody,
  SESSION_TOKEN,
  SYMFONY,
  symfonyAnswers,
} from "@tests/symfony-api"
import { PATCH } from "./route"

vi.mock("server-only", () => ({}))

vi.mock("next/headers", () => ({
  cookies: async () => ({ get: () => ({ value: SESSION_TOKEN }) }),
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
  vi.unstubAllGlobals()
})

describe("PATCH /api/fights/[id]/status", () => {
  it("sends the scores an administrator imposes, keyed by side", async () => {
    const requests = symfonyAnswers(200)

    await PATCH(
      patchRequest({ status: "finished", scores: { [ONE]: 3, [TWO]: 1 } }),
      context(FIGHT_ID)
    )

    expect(requests[0]).toMatchObject({
      url: `${SYMFONY}/api/admin/fights/${FIGHT_ID}/status`,
      method: "PATCH",
      authorization: `Bearer ${SESSION_TOKEN}`,
    })
    expect(jsonBody(requests[0])).toEqual({
      status: "finished",
      scores: { [ONE]: 3, [TWO]: 1 },
    })
  })

  it("sets a declaration aside with the status alone", async () => {
    const requests = symfonyAnswers(200)

    await PATCH(
      patchRequest({ status: "pending", scores: { [ONE]: 3 } }),
      context(FIGHT_ID)
    )

    expect(jsonBody(requests[0])).toEqual({ status: "pending" })
  })

  it("refuses a negative score, without asking the backend", async () => {
    const requests = symfonyAnswers(200)

    const response = await PATCH(
      patchRequest({ status: "finished", scores: { [ONE]: -1, [TWO]: 2 } }),
      context(FIGHT_ID)
    )

    expect(response.status).toBe(400)
    expect(requests).toHaveLength(0)
  })

  it("refuses a status the backend does not know", async () => {
    const requests = symfonyAnswers(200)

    const response = await PATCH(
      patchRequest({ status: "reporting" }),
      context(FIGHT_ID)
    )

    expect(response.status).toBe(400)
    expect(requests).toHaveLength(0)
  })
})
