import { afterEach, describe, expect, it, vi } from "vitest"
import userEvent from "@testing-library/user-event"
import { renderWithProviders, screen, waitFor } from "@tests/test-utils"
import { ChallengeTeamButton } from "./challenge-team-button"

const refresh = vi.fn()
vi.mock("next/navigation", () => ({ useRouter: () => ({ refresh }) }))

const add = vi.fn()
vi.mock("@/components/ui/toast", () => ({
  toast: { add: (...args: unknown[]) => add(...args) },
}))

const MINE = "11111111-1111-4111-8111-111111111111"
const THEIRS = "22222222-2222-4222-8222-222222222222"

function backendAnswers(status: number, body: unknown) {
  vi.stubGlobal(
    "fetch",
    vi.fn(
      async () =>
        new Response(JSON.stringify(body), {
          status,
          headers: { "Content-Type": "application/json" },
        })
    )
  )
}

async function challenge() {
  renderWithProviders(
    <ChallengeTeamButton
      myTeamId={MINE}
      myTeamName="Falcons"
      theirTeamId={THEIRS}
      theirTeamName="Rivals"
    />
  )

  await userEvent.click(screen.getByRole("button", { name: /Falcons/ }))
}

afterEach(() => {
  vi.unstubAllGlobals()
  vi.clearAllMocks()
})

describe("ChallengeTeamButton", () => {
  it("says a team fielding the profile of a deleted account must be composed again", async () => {
    backendAnswers(409, {
      message: "this team fields the profile of a deleted account",
      code: "teamFieldsDeletedAccount",
    })

    await challenge()

    await waitFor(() =>
      expect(add).toHaveBeenCalledWith(
        expect.objectContaining({
          type: "error",
          description:
            "One of the two teams fields the profile of a deleted account: its clan leader has to compose a new team.",
        })
      )
    )
  })

  it("says the two teams cannot fight when the API refuses the pairing", async () => {
    backendAnswers(400, { message: "these teams cannot fight" })

    await challenge()

    await waitFor(() =>
      expect(add).toHaveBeenCalledWith(
        expect.objectContaining({
          type: "error",
          description:
            "These two teams cannot fight: same format, same game, and no player in common.",
        })
      )
    )
  })
})
