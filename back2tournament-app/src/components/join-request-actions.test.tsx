import { afterEach, describe, expect, it, vi } from "vitest"
import userEvent from "@testing-library/user-event"
import { renderWithProviders, screen, waitFor } from "@tests/test-utils"
import { JoinRequestActions } from "./join-request-actions"

const refresh = vi.fn()
vi.mock("next/navigation", () => ({ useRouter: () => ({ refresh }) }))

const add = vi.fn()
vi.mock("@/components/ui/toast", () => ({
  toast: { add: (...args: unknown[]) => add(...args) },
}))

const CLAN = "11111111-1111-4111-8111-111111111111"
const PLAYER = "22222222-2222-4222-8222-222222222222"

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

function renderActions() {
  renderWithProviders(
    <JoinRequestActions
      clanId={CLAN}
      playerId={PLAYER}
      battletag="Blitz#1113"
    />
  )
}

afterEach(() => {
  vi.unstubAllGlobals()
  vi.clearAllMocks()
})

describe("JoinRequestActions", () => {
  it("accepts the player into the clan", async () => {
    const fetchMock = backendAnswers(200, { status: "active" })
    renderActions()

    await userEvent.click(
      screen.getByRole("button", { name: "Accept Blitz#1113 into the clan" })
    )

    await waitFor(() => expect(fetchMock).toHaveBeenCalledTimes(1))
    const [url, init] = fetchMock.mock.calls[0]!
    expect(url).toBe(`/api/clans/${CLAN}/admissions`)
    expect(init?.method).toBe("POST")
    expect(JSON.parse(String(init?.body))).toEqual({ player: PLAYER })
    await waitFor(() => expect(refresh).toHaveBeenCalled())
    expect(add).toHaveBeenCalledWith(
      expect.objectContaining({
        type: "success",
        title: "Blitz#1113 joined the clan",
      })
    )
  })

  it("declines the request by taking the player's place in the clan away", async () => {
    const fetchMock = backendAnswers(200, { status: "requested" })
    renderActions()

    await userEvent.click(
      screen.getByRole("button", { name: "Decline the request of Blitz#1113" })
    )

    await waitFor(() => expect(fetchMock).toHaveBeenCalledTimes(1))
    const [url, init] = fetchMock.mock.calls[0]!
    expect(url).toBe(`/api/clans/${CLAN}/members/${PLAYER}`)
    expect(init?.method).toBe("DELETE")
    await waitFor(() =>
      expect(add).toHaveBeenCalledWith(
        expect.objectContaining({
          type: "success",
          title: "Request of Blitz#1113 declined",
        })
      )
    )
  })

  it("says the player joined another clan when the API refuses the admission", async () => {
    backendAnswers(409, {
      message: "this player already plays for another clan",
    })
    renderActions()

    await userEvent.click(
      screen.getByRole("button", { name: "Accept Blitz#1113 into the clan" })
    )

    await waitFor(() =>
      expect(add).toHaveBeenCalledWith(
        expect.objectContaining({
          type: "error",
          description:
            "This player joined another clan in this game in the meantime.",
        })
      )
    )
    expect(refresh).toHaveBeenCalled()
  })

  it("says the player withdrew the request when it is gone", async () => {
    backendAnswers(404, { message: "this player did not ask to join the clan" })
    renderActions()

    await userEvent.click(
      screen.getByRole("button", { name: "Accept Blitz#1113 into the clan" })
    )

    await waitFor(() =>
      expect(add).toHaveBeenCalledWith(
        expect.objectContaining({
          type: "error",
          description: "This player withdrew their request.",
        })
      )
    )
  })
})
