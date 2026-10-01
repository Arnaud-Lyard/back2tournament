import { afterEach, describe, expect, it, vi } from "vitest"
import userEvent from "@testing-library/user-event"
import { renderWithProviders, screen, waitFor } from "@tests/test-utils"
import { DissolveClanForm } from "./dissolve-clan-form"

const refresh = vi.fn()
const replace = vi.fn()
vi.mock("next/navigation", () => ({ useRouter: () => ({ refresh, replace }) }))

const add = vi.fn()
vi.mock("@/components/ui/toast", () => ({
  toast: { add: (...args: unknown[]) => add(...args) },
}))

const CLAN = "11111111-1111-4111-8111-111111111111"
const GAME = "33333333-3333-4333-8333-333333333333"

function backendAnswers(status: number, body: unknown) {
  const fetchMock = vi.fn(
    async (_input: string, _init?: RequestInit) =>
      new Response(JSON.stringify(body), {
        status,
        headers: { "Content-Type": "application/json" },
      })
  )
  vi.stubGlobal("fetch", fetchMock)
  return fetchMock
}

async function dissolveWith(password: string) {
  renderWithProviders(
    <DissolveClanForm clanId={CLAN} gameId={GAME} clanName="Rivals" />
  )

  await userEvent.click(
    screen.getByRole("button", { name: "Dissolve the clan" })
  )
  await userEvent.type(
    screen.getByLabelText("Confirm with your password"),
    password
  )
  await userEvent.click(
    screen.getByRole("button", { name: "Dissolve for good" })
  )
}

afterEach(() => {
  vi.unstubAllGlobals()
  vi.clearAllMocks()
})

describe("DissolveClanForm", () => {
  it("dissolves the clan with the password and goes back to the clans of the game", async () => {
    const fetchMock = backendAnswers(200, { dissolvedAt: "2026-10-01" })

    await dissolveWith("Secret1!")

    await waitFor(() =>
      expect(replace).toHaveBeenCalledWith(`/games/${GAME}/clans`)
    )
    const [url, init] = fetchMock.mock.calls[0]!
    expect(url).toBe(`/api/clans/${CLAN}/dissolution`)
    expect(init?.method).toBe("POST")
    expect(JSON.parse(String(init?.body))).toEqual({ password: "Secret1!" })
    expect(add).toHaveBeenCalledWith(
      expect.objectContaining({ type: "success", title: "Rivals is dissolved" })
    )
  })

  it.each([
    [
      403,
      { message: "the password does not match", code: "wrongPassword" },
      "The password does not match.",
    ],
    [
      403,
      { message: "only the clan leader dissolves the clan" },
      "Only the clan leader can dissolve it.",
    ],
    [
      404,
      { message: "clan not found" },
      "This clan no longer exists or is already dissolved.",
    ],
  ])("explains a %s refusal", async (status, body, description) => {
    backendAnswers(status, body)

    await dissolveWith("Secret1!")

    await waitFor(() =>
      expect(add).toHaveBeenCalledWith(
        expect.objectContaining({ type: "error", description })
      )
    )
    expect(replace).not.toHaveBeenCalled()
  })
})
