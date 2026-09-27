import { afterEach, describe, expect, it, vi } from "vitest"
import userEvent from "@testing-library/user-event"
import { renderWithProviders, screen, waitFor } from "@tests/test-utils"
import { ChallengeActions } from "./challenge-actions"

const refresh = vi.fn()
vi.mock("next/navigation", () => ({ useRouter: () => ({ refresh }) }))

const add = vi.fn()
vi.mock("@/components/ui/toast", () => ({
  toast: { add: (...args: unknown[]) => add(...args) },
}))

const FIGHT_ID = "11111111-1111-4111-8111-111111111111"

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

describe("ChallengeActions", () => {
  it("declares the scores from the caller's side", async () => {
    const fetchMock = backendAnswers(200, {})
    renderWithProviders(
      <ChallengeActions
        fightId={FIGHT_ID}
        stage="declare"
        score={0}
        opponentScore={0}
      />
    )

    await userEvent.type(screen.getByLabelText("My score"), "3")
    await userEvent.type(screen.getByLabelText("Opponent score"), "1")
    await userEvent.click(
      screen.getByRole("button", { name: "Declare the result" })
    )

    await waitFor(() => expect(fetchMock).toHaveBeenCalledTimes(1))
    const [url, init] = fetchMock.mock.calls[0]!
    expect(url).toBe(`/api/fights/${FIGHT_ID}/results`)
    expect(init?.method).toBe("PATCH")
    expect(JSON.parse(String(init?.body))).toEqual({
      score: 3,
      opponentScore: 1,
    })
    await waitFor(() => expect(refresh).toHaveBeenCalled())
  })

  it("refuses a score that is not a whole number without calling the server", async () => {
    const fetchMock = backendAnswers(200, {})
    renderWithProviders(
      <ChallengeActions
        fightId={FIGHT_ID}
        stage="declare"
        score={0}
        opponentScore={0}
      />
    )

    await userEvent.type(screen.getByLabelText("My score"), "1.5")
    await userEvent.type(screen.getByLabelText("Opponent score"), "1")
    await userEvent.click(
      screen.getByRole("button", { name: "Declare the result" })
    )

    expect(
      await screen.findByText("A whole number, zero or more, is expected.")
    ).toBeInTheDocument()
    expect(fetchMock).not.toHaveBeenCalled()
  })

  it("lets the declaring side correct what it declared", async () => {
    renderWithProviders(
      <ChallengeActions
        fightId={FIGHT_ID}
        stage="awaiting"
        score={3}
        opponentScore={1}
      />
    )

    await userEvent.click(
      screen.getByRole("button", { name: "Correct my declaration" })
    )

    expect(screen.getByLabelText("My score")).toHaveValue(3)
    expect(screen.getByLabelText("Opponent score")).toHaveValue(1)
  })

  it("confirms what the other side declared", async () => {
    const fetchMock = backendAnswers(200, {})
    renderWithProviders(
      <ChallengeActions
        fightId={FIGHT_ID}
        stage="confirm"
        score={1}
        opponentScore={3}
      />
    )

    await userEvent.click(
      screen.getByRole("button", { name: "Confirm the result" })
    )

    await waitFor(() => expect(fetchMock).toHaveBeenCalledTimes(1))
    const [url, init] = fetchMock.mock.calls[0]!
    expect(url).toBe(`/api/fights/${FIGHT_ID}/confirmation`)
    expect(init?.method).toBe("POST")
  })

  it("words a refused confirmation for what it means", async () => {
    backendAnswers(403, {
      message: "the declaring side cannot confirm its own outcome",
    })
    renderWithProviders(
      <ChallengeActions
        fightId={FIGHT_ID}
        stage="confirm"
        score={1}
        opponentScore={3}
      />
    )

    await userEvent.click(
      screen.getByRole("button", { name: "Confirm the result" })
    )

    await waitFor(() =>
      expect(add).toHaveBeenCalledWith(
        expect.objectContaining({
          type: "error",
          description: "The side that declared cannot confirm its own result.",
        })
      )
    )
  })
})
