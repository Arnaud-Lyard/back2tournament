import { afterEach, describe, expect, it, vi } from "vitest"
import userEvent from "@testing-library/user-event"
import { renderWithProviders, screen, waitFor } from "@tests/test-utils"
import { ArbitrationForm } from "./arbitration-form"

const refresh = vi.fn()
vi.mock("next/navigation", () => ({ useRouter: () => ({ refresh }) }))

const add = vi.fn()
vi.mock("@/components/ui/toast", () => ({
  toast: { add: (...args: unknown[]) => add(...args) },
}))

const FIGHT_ID = "11111111-1111-4111-8111-111111111111"
const ONE = "22222222-2222-4222-8222-222222222222"
const TWO = "33333333-3333-4333-8333-333333333333"

const alpha = { competitor: ONE, name: "Alpha#0001", score: 3 }
const bravo = { competitor: TWO, name: "Bravo#0002", score: 1 }

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

describe("ArbitrationForm", () => {
  it("starts from the declaration in dispute and imposes the scores once confirmed", async () => {
    const fetchMock = backendAnswers(200, {})
    renderWithProviders(
      <ArbitrationForm
        fightId={FIGHT_ID}
        one={alpha}
        two={bravo}
        declared
        tournament={false}
      />
    )

    expect(screen.getByLabelText("Alpha#0001's score")).toHaveValue(3)
    await userEvent.clear(screen.getByLabelText("Alpha#0001's score"))
    await userEvent.type(screen.getByLabelText("Alpha#0001's score"), "0")
    await userEvent.click(
      screen.getByRole("button", { name: "Impose this result" })
    )

    // Nothing is sent before the confirmation.
    expect(fetchMock).not.toHaveBeenCalled()
    expect(
      screen.getByText("Impose Alpha#0001 0 – 1 Bravo#0002?")
    ).toBeInTheDocument()
    await userEvent.click(
      screen.getByRole("button", { name: "Yes, impose it" })
    )

    await waitFor(() => expect(fetchMock).toHaveBeenCalledTimes(1))
    const [url, init] = fetchMock.mock.calls[0]!
    expect(url).toBe(`/api/fights/${FIGHT_ID}/status`)
    expect(init?.method).toBe("PATCH")
    expect(JSON.parse(String(init?.body))).toEqual({
      status: "finished",
      scores: { [ONE]: 0, [TWO]: 1 },
    })
    await waitFor(() => expect(refresh).toHaveBeenCalled())
  })

  it("does not impose a draw on a tournament fight", async () => {
    const fetchMock = backendAnswers(200, {})
    renderWithProviders(
      <ArbitrationForm
        fightId={FIGHT_ID}
        one={alpha}
        two={bravo}
        declared={false}
        tournament
      />
    )

    await userEvent.type(screen.getByLabelText("Alpha#0001's score"), "2")
    await userEvent.type(screen.getByLabelText("Bravo#0002's score"), "2")
    await userEvent.click(
      screen.getByRole("button", { name: "Impose this result" })
    )

    expect(screen.getByRole("alert")).toHaveTextContent("No draws")
    expect(screen.queryByRole("button", { name: "Yes, impose it" })).toBeNull()
    expect(fetchMock).not.toHaveBeenCalled()
  })

  it("sets a declaration aside", async () => {
    const fetchMock = backendAnswers(200, {})
    renderWithProviders(
      <ArbitrationForm
        fightId={FIGHT_ID}
        one={alpha}
        two={bravo}
        declared
        tournament={false}
      />
    )

    await userEvent.click(
      screen.getByRole("button", { name: "Back to declare" })
    )

    await waitFor(() => expect(fetchMock).toHaveBeenCalledTimes(1))
    expect(JSON.parse(String(fetchMock.mock.calls[0]![1]?.body))).toEqual({
      status: "pending",
    })
  })

  it("offers no setting aside when nothing was declared", () => {
    backendAnswers(200, {})
    renderWithProviders(
      <ArbitrationForm
        fightId={FIGHT_ID}
        one={alpha}
        two={bravo}
        declared={false}
        tournament={false}
      />
    )

    expect(screen.getByLabelText("Alpha#0001's score")).toHaveValue(null)
    expect(screen.queryByRole("button", { name: "Back to declare" })).toBeNull()
  })
})
