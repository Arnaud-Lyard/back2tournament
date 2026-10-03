// @vitest-environment node
import { afterEach, describe, expect, it, vi } from "vitest"
import { LOCALE_COOKIE_NAME } from "@/features/i18n/routing"
import { jsonBody, SYMFONY, symfonyAnswers } from "@tests/symfony-api"
import { POST } from "./route"

vi.mock("server-only", () => ({}))

const cookieJar = new Map<string, string>()

vi.mock("next/headers", () => ({
  cookies: async () => ({
    get: (name: string) =>
      cookieJar.has(name) ? { name, value: cookieJar.get(name) } : undefined,
  }),
}))

const registration = {
  email: "jane@example.com",
  username: "jane_doe",
  password: "Password1!",
  passwordConfirmation: "Password1!",
}

async function register() {
  const requests = symfonyAnswers(200)

  await POST(
    new Request("http://localhost/api/auth/register", {
      method: "POST",
      body: JSON.stringify(registration),
    })
  )

  expect(requests).toHaveLength(1)
  expect(requests[0]).toMatchObject({
    url: `${SYMFONY}/api/register`,
    method: "POST",
    authorization: null,
  })
  return jsonBody(requests[0]) as Record<string, unknown>
}

afterEach(() => {
  cookieJar.clear()
  vi.unstubAllGlobals()
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
