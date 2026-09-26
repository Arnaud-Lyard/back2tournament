import { afterEach, describe, expect, it, vi } from "vitest"
import userEvent from "@testing-library/user-event"
import { renderWithProviders, screen, waitFor } from "@tests/test-utils"
import type { AuthUser } from "@/features/auth/types"
import { ChallengeButton } from "./challenge-button"

const refresh = vi.fn()
vi.mock("next/navigation", () => ({ useRouter: () => ({ refresh }) }))

const add = vi.fn()
vi.mock("@/components/ui/toast", () => ({
  toast: { add: (...args: unknown[]) => add(...args) },
}))

const GAME_ID = "11111111-1111-4111-8111-111111111111"
const MY_PLAYER_ID = "22222222-2222-4222-8222-222222222222"
const TARGET_PLAYER_ID = "33333333-3333-4333-8333-333333333333"

function userWithProfile(gameId: string): AuthUser {
  return {
    username: "jane",
    role: "user",
    permissions: [],
    verified: true,
    playersByGame: {
      [gameId]: { id: MY_PLAYER_ID, battletag: "Alpha#1234" },
    },
  }
}

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

function renderButton(user: AuthUser | null) {
  return renderWithProviders(
    <ChallengeButton
      gameId={GAME_ID}
      playerId={TARGET_PLAYER_ID}
      battletag="Bravo#5678"
    />,
    { user }
  )
}

afterEach(() => {
  vi.unstubAllGlobals()
  vi.clearAllMocks()
})

describe("ChallengeButton", () => {
  it("sends a visitor to sign in rather than to a button that cannot work", () => {
    renderButton(null)

    expect(screen.getByRole("link", { name: "Sign in" })).toHaveAttribute(
      "href",
      "/login"
    )
    expect(
      screen.queryByRole("button", { name: /Challenge/ })
    ).not.toBeInTheDocument()
  })

  it("asks a player without a profile in this game to create one first", () => {
    renderButton(userWithProfile("44444444-4444-4444-8444-444444444444"))

    expect(
      screen.getByRole("link", { name: "Create my profile" })
    ).toHaveAttribute("href", `/players/new?gameId=${GAME_ID}`)
  })

  it("does not offer to challenge your own profile", () => {
    renderWithProviders(
      <ChallengeButton
        gameId={GAME_ID}
        playerId={MY_PLAYER_ID}
        battletag="Alpha#1234"
      />,
      { user: userWithProfile(GAME_ID) }
    )

    expect(
      screen.getByText("This is your own profile in this game.")
    ).toBeInTheDocument()
    expect(
      screen.queryByRole("button", { name: /Challenge/ })
    ).not.toBeInTheDocument()
  })

  it("opens the fight between the caller's profile and the one shown", async () => {
    const fetchMock = backendAnswers(200, { id: { value: "fight" } })
    renderButton(userWithProfile(GAME_ID))

    await userEvent.click(
      screen.getByRole("button", { name: "Challenge Bravo#5678" })
    )

    await waitFor(() => expect(fetchMock).toHaveBeenCalledOnce())
    const [url, init] = fetchMock.mock.calls[0]
    expect(url).toBe("/api/fights")
    expect(JSON.parse(String(init?.body))).toEqual({
      playerOne: MY_PLAYER_ID,
      playerTwo: TARGET_PLAYER_ID,
    })
    await waitFor(() => expect(refresh).toHaveBeenCalled())
  })

  it("goes once the fight is open, so a second click cannot double it", async () => {
    backendAnswers(200, { id: { value: "fight" } })
    renderButton(userWithProfile(GAME_ID))

    await userEvent.click(
      screen.getByRole("button", { name: "Challenge Bravo#5678" })
    )

    await waitFor(() =>
      expect(
        screen.queryByRole("button", { name: "Challenge Bravo#5678" })
      ).not.toBeInTheDocument()
    )
  })

  it("words a refusal from the backend instead of the raw message", async () => {
    backendAnswers(403, { message: "you do not take part in this fight" })
    renderButton(userWithProfile(GAME_ID))

    await userEvent.click(
      screen.getByRole("button", { name: "Challenge Bravo#5678" })
    )

    await waitFor(() =>
      expect(add).toHaveBeenCalledWith(
        expect.objectContaining({
          type: "error",
          description: "You can only challenge from your own profile.",
        })
      )
    )
  })
})
