// @vitest-environment node
import { afterEach, describe, expect, it, vi } from "vitest"
import { SYMFONY } from "@tests/symfony-api"
import { succeeded, symfonyFetch } from "./symfony-fetch"

vi.mock("server-only", () => ({}))

function backendAnswers(response: Response) {
  const fetchMock = vi.fn(async () => response)
  vi.stubGlobal("fetch", fetchMock)
  return fetchMock
}

afterEach(() => {
  vi.unstubAllGlobals()
})

describe("symfonyFetch", () => {
  it("calls the API at its base URL with the request as built", async () => {
    const fetchMock = backendAnswers(Response.json({ id: 1 }))
    const init = { method: "POST", body: "{}" }

    await symfonyFetch("/api/user/clans/", init)

    expect(fetchMock).toHaveBeenCalledWith(`${SYMFONY}/api/user/clans/`, init)
  })

  it("answers the status and the JSON body, whatever the status", async () => {
    backendAnswers(Response.json({ error: "clan not found" }, { status: 404 }))

    const answer = await symfonyFetch<{ data: unknown; status: number }>(
      "/api/clans/x",
      { method: "GET" }
    )

    expect(answer).toMatchObject({
      status: 404,
      data: { error: "clan not found" },
    })
  })

  it("keeps a body that is not JSON as text, such as an error page", async () => {
    backendAnswers(new Response("<html>oops</html>", { status: 500 }))

    const answer = await symfonyFetch<{ data: unknown; status: number }>(
      "/api/games/",
      { method: "GET" }
    )

    expect(answer).toMatchObject({ status: 500, data: "<html>oops</html>" })
  })

  it("answers no data for a response without a body", async () => {
    backendAnswers(new Response(null, { status: 204 }))

    const answer = await symfonyFetch<{ data: unknown; status: number }>(
      "/api/user/logout",
      { method: "POST" }
    )

    expect(answer).toMatchObject({ status: 204, data: undefined })
  })
})

describe("succeeded", () => {
  it.each([
    [200, true],
    [204, true],
    [302, false],
    [404, false],
    [500, false],
  ])("tells whether %i is a success", (status, expected) => {
    expect(succeeded(status)).toBe(expected)
  })
})
