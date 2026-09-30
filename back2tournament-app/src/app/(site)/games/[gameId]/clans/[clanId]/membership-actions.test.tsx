import { afterEach, describe, expect, it, vi } from "vitest"
import userEvent from "@testing-library/user-event"
import { renderWithProviders, screen, waitFor } from "@tests/test-utils"
import { MembershipActions } from "./membership-actions"
import { RequestToJoinButton } from "./request-to-join-button"

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

afterEach(() => {
  vi.unstubAllGlobals()
  vi.clearAllMocks()
})

describe("RequestToJoinButton", () => {
  it("asks the clan leader to let the player in", async () => {
    const fetchMock = backendAnswers(200, { status: "requested" })
    renderWithProviders(<RequestToJoinButton clanId={CLAN} />)

    await userEvent.click(screen.getByRole("button", { name: "Ask to join" }))

    await waitFor(() => expect(fetchMock).toHaveBeenCalledTimes(1))
    const [url, init] = fetchMock.mock.calls[0]!
    expect(url).toBe(`/api/clans/${CLAN}/requests`)
    expect(init?.method).toBe("POST")
    await waitFor(() => expect(refresh).toHaveBeenCalled())
    expect(add).toHaveBeenCalledWith(
      expect.objectContaining({
        type: "success",
        title: "Request sent to the clan leader",
      })
    )
  })
})

describe("MembershipActions", () => {
  it("lets a player withdraw the request the leader has not answered", async () => {
    const fetchMock = backendAnswers(200, { status: "requested" })
    renderWithProviders(
      <MembershipActions clanId={CLAN} playerId={PLAYER} status="requested" />
    )

    expect(
      screen.queryByRole("button", { name: "Leave the clan" })
    ).not.toBeInTheDocument()
    await userEvent.click(
      screen.getByRole("button", { name: "Withdraw my request" })
    )

    await waitFor(() => expect(fetchMock).toHaveBeenCalledTimes(1))
    const [url, init] = fetchMock.mock.calls[0]!
    expect(url).toBe(`/api/clans/${CLAN}/members/${PLAYER}`)
    expect(init?.method).toBe("DELETE")
    await waitFor(() =>
      expect(add).toHaveBeenCalledWith(
        expect.objectContaining({ type: "success", title: "Request withdrawn" })
      )
    )
  })
})
