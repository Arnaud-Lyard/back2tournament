// @vitest-environment node
import { afterEach, describe, expect, it, vi } from "vitest"
import { AUTH_COOKIE_NAME } from "@/features/auth/lib/cookie"
import { POST } from "./route"

vi.mock("server-only", () => ({}))

const backendPost = vi.fn()
const deleteCookie = vi.fn()

vi.mock("next/headers", () => ({
  cookies: async () => ({ delete: deleteCookie }),
}))

vi.mock("@/libs/api/client", () => ({
  getServerApiClient: async () => ({ POST: backendPost }),
}))

function deletion(body: unknown) {
  return new Request("http://localhost/api/users/me/deletion", {
    method: "POST",
    body: JSON.stringify(body),
  })
}

afterEach(() => {
  backendPost.mockReset()
  deleteCookie.mockReset()
})

describe("POST /api/users/me/deletion", () => {
  it("deletes the signed-in account with the password and signs it out", async () => {
    backendPost.mockResolvedValue({
      data: { deleted: true },
      response: new Response(null),
    })

    const response = await POST(deletion({ password: "Secret1!" }))

    expect(response.status).toBe(200)
    const [path, options] = backendPost.mock.calls[0]
    expect(path).toBe("/api/user/me/deletion")
    expect(options.body).toEqual({ password: "Secret1!" })
    expect(deleteCookie).toHaveBeenCalledWith(AUTH_COOKIE_NAME)
  })

  it("keeps the session when the backend refuses the password", async () => {
    backendPost.mockResolvedValue({
      error: { error: "the password does not match" },
      response: new Response(null, { status: 403 }),
    })

    const response = await POST(deletion({ password: "wrong" }))

    expect(response.status).toBe(403)
    expect(await response.json()).toEqual({
      message: "the password does not match",
      code: "wrongPassword",
    })
    expect(deleteCookie).not.toHaveBeenCalled()
  })

  it("refuses an empty password, without asking the backend", async () => {
    const response = await POST(deletion({ password: "" }))

    expect(response.status).toBe(400)
    expect(backendPost).not.toHaveBeenCalled()
    expect(deleteCookie).not.toHaveBeenCalled()
  })
})
