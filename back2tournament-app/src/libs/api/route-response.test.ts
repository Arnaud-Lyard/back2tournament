import { describe, expect, it, vi } from "vitest"
import { relayApiResult } from "./route-response"

vi.mock("server-only", () => ({}))

describe("relayApiResult", () => {
  it("relays the payload of a successful call", async () => {
    const response = await relayApiResult(
      Promise.resolve({ data: { id: 1 }, response: new Response(null) })
    )

    expect(response.status).toBe(200)
    expect(await response.json()).toEqual({ id: 1 })
  })

  it("relays the backend status and message of a refusal", async () => {
    const response = await relayApiResult(
      Promise.resolve({
        error: { error: "you do not take part in this fight" },
        response: new Response(null, { status: 403 }),
      })
    )

    expect(response.status).toBe(403)
    expect(await response.json()).toEqual({
      message: "you do not take part in this fight",
    })
  })

  it("tags a failure the UI words on its own with a code", async () => {
    const response = await relayApiResult(
      Promise.resolve({
        error: { error: "username already used" },
        response: new Response(null, { status: 409 }),
      })
    )

    expect(response.status).toBe(409)
    expect(await response.json()).toEqual({
      message: "username already used",
      code: "usernameTaken",
    })
  })

  it("still reports an error whose body was empty", async () => {
    const response = await relayApiResult(
      Promise.resolve({
        error: undefined,
        response: new Response(null, { status: 404 }),
      })
    )

    expect(response.status).toBe(404)
    expect(await response.json()).toEqual({
      message: "Request failed with status 404",
    })
  })

  it("answers 502 when the backend cannot be reached", async () => {
    const response = await relayApiResult(
      Promise.reject(new TypeError("fetch failed"))
    )

    expect(response.status).toBe(502)
  })
})
