import { afterEach, describe, expect, it, vi } from "vitest"
import { LOCALE_COOKIE_NAME } from "@/features/i18n/routing"
import { POST } from "./route"

vi.mock("server-only", () => ({}))

const cookieJar = new Map<string, string>()
const backendPost = vi.fn()

vi.mock("next/headers", () => ({
  cookies: async () => ({
    get: (name: string) =>
      cookieJar.has(name) ? { name, value: cookieJar.get(name) } : undefined,
  }),
}))

vi.mock("@/libs/api/client", () => ({
  createApiClient: () => ({ POST: backendPost }),
}))

const registration = {
  email: "jane@example.com",
  username: "jane_doe",
  password: "Password1!",
  passwordConfirmation: "Password1!",
}

/** Registers through the route and returns what it sent to the backend. */
async function register() {
  backendPost.mockResolvedValue({ data: {}, response: new Response(null) })

  await POST(
    new Request("http://localhost/api/auth/register", {
      method: "POST",
      body: JSON.stringify(registration),
    })
  )

  expect(backendPost).toHaveBeenCalledOnce()
  const [path, { body }] = backendPost.mock.calls[0]
  expect(path).toBe("/api/register")
  return body
}

afterEach(() => {
  cookieJar.clear()
  backendPost.mockReset()
})

describe("POST /api/auth/register", () => {
  it("sends the language picked in the header to the backend", async () => {
    cookieJar.set(LOCALE_COOKIE_NAME, "en")

    expect(await register()).toEqual({ ...registration, locale: "en" })
  })

  it("sends the default language when none was picked", async () => {
    expect((await register()).locale).toBe("fr")
  })
})
