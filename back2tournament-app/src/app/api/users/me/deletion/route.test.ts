// @vitest-environment node
import { afterEach, describe, expect, it, vi } from "vitest"
import { AUTH_COOKIE_NAME } from "@/features/auth/lib/cookie"
import {
  jsonBody,
  SESSION_TOKEN,
  SYMFONY,
  symfonyAnswers,
} from "@tests/symfony-api"
import { POST } from "./route"

vi.mock("server-only", () => ({}))

const deleteCookie = vi.fn()

vi.mock("next/headers", () => ({
  cookies: async () => ({
    get: () => ({ value: SESSION_TOKEN }),
    delete: deleteCookie,
  }),
}))

function deletion(body: unknown) {
  return new Request("http://localhost/api/users/me/deletion", {
    method: "POST",
    body: JSON.stringify(body),
  })
}

afterEach(() => {
  vi.unstubAllGlobals()
  deleteCookie.mockReset()
})

describe("POST /api/users/me/deletion", () => {
  it("deletes the signed-in account with the password and signs it out", async () => {
    const requests = symfonyAnswers(200, { deleted: true })

    const response = await POST(deletion({ password: "Secret1!" }))

    expect(response.status).toBe(200)
    expect(requests[0]).toMatchObject({
      url: `${SYMFONY}/api/user/me/deletion`,
      method: "POST",
      authorization: `Bearer ${SESSION_TOKEN}`,
    })
    expect(jsonBody(requests[0])).toEqual({ password: "Secret1!" })
    expect(deleteCookie).toHaveBeenCalledWith(AUTH_COOKIE_NAME)
  })

  it("keeps the session when the backend refuses the password", async () => {
    symfonyAnswers(403, { error: "the password does not match" })

    const response = await POST(deletion({ password: "wrong" }))

    expect(response.status).toBe(403)
    expect(await response.json()).toEqual({
      message: "the password does not match",
      code: "wrongPassword",
    })
    expect(deleteCookie).not.toHaveBeenCalled()
  })

  it("refuses an empty password, without asking the backend", async () => {
    const requests = symfonyAnswers(200)

    const response = await POST(deletion({ password: "" }))

    expect(response.status).toBe(400)
    expect(requests).toHaveLength(0)
    expect(deleteCookie).not.toHaveBeenCalled()
  })
})
