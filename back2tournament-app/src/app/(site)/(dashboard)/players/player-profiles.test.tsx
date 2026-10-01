import { afterEach, describe, expect, it, vi } from "vitest"
import userEvent from "@testing-library/user-event"
import { renderWithProviders, screen, waitFor } from "@tests/test-utils"
import type { AuthUser } from "@/features/auth/types"
import { PlayerProfiles } from "./player-profiles"

const refresh = vi.fn()
vi.mock("next/navigation", () => ({ useRouter: () => ({ refresh }) }))

const add = vi.fn()
vi.mock("@/components/ui/toast", () => ({
  toast: { add: (...args: unknown[]) => add(...args) },
}))

const GAME_ID = "11111111-1111-4111-8111-111111111111"
const PLAYER_ID = "22222222-2222-4222-8222-222222222222"

const games = [{ id: { value: GAME_ID }, title: "Street Fighter 6" }]

const user: AuthUser = {
  username: "jane",
  role: "user",
  permissions: [],
  verified: true,
  avatar: null,
  playersByGame: { [GAME_ID]: { id: PLAYER_ID, battletag: "PlayerOne#1234" } },
}

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

afterEach(() => {
  vi.unstubAllGlobals()
  add.mockReset()
  refresh.mockReset()
})

describe("PlayerProfiles", () => {
  it("toasts the refusal when the profile takes part in fights", async () => {
    backendAnswers(409, {
      message: "this player profile takes part in fights and cannot be deleted",
      code: "playerHasFights",
    })
    renderWithProviders(<PlayerProfiles games={games} />, { user })

    await userEvent.click(screen.getByRole("button", { name: "Delete" }))
    await userEvent.click(screen.getByRole("button", { name: "Confirm" }))

    await waitFor(() =>
      expect(add).toHaveBeenCalledWith({
        type: "error",
        title: "The profile could not be deleted",
        description:
          "This profile takes part in fights: it can no longer be deleted.",
      })
    )
    expect(refresh).not.toHaveBeenCalled()
  })

  it.each([
    [
      "playerLeadsClan",
      "this player profile leads a clan and cannot be deleted",
      "This profile leads a clan: it cannot be deleted.",
    ],
    [
      "playerInClan",
      "this player profile belongs to a clan: leave it first",
      "This profile is a member of a clan: leave it before deleting the profile.",
    ],
    [
      "playerInvitedToClan",
      "this player profile is invited to a clan: decline the invitation first",
      "This profile is invited to a clan: decline the invitation before deleting the profile.",
    ],
    [
      "playerAsksToJoinClan",
      "this player profile asks to join a clan: withdraw the request first",
      "This profile asked to join a clan: withdraw the request before deleting the profile.",
    ],
  ])(
    "says what holds the profile in a clan (%s)",
    async (code, message, description) => {
      backendAnswers(409, { message, code })
      renderWithProviders(<PlayerProfiles games={games} />, { user })

      await userEvent.click(screen.getByRole("button", { name: "Delete" }))
      await userEvent.click(screen.getByRole("button", { name: "Confirm" }))

      await waitFor(() =>
        expect(add).toHaveBeenCalledWith({
          type: "error",
          title: "The profile could not be deleted",
          description,
        })
      )
    }
  )

  it("does not blame fights for a conflict it cannot name", async () => {
    backendAnswers(409, { message: "something else entirely" })
    renderWithProviders(<PlayerProfiles games={games} />, { user })

    await userEvent.click(screen.getByRole("button", { name: "Delete" }))
    await userEvent.click(screen.getByRole("button", { name: "Confirm" }))

    await waitFor(() => expect(add).toHaveBeenCalled())
    expect(add.mock.calls[0]?.[0]?.description).not.toMatch(/fights/)
  })

  it("deletes a profile that never fought", async () => {
    backendAnswers(200, { battletag: "PlayerOne#1234" })
    renderWithProviders(<PlayerProfiles games={games} />, { user })

    await userEvent.click(screen.getByRole("button", { name: "Delete" }))
    await userEvent.click(screen.getByRole("button", { name: "Confirm" }))

    await waitFor(() => expect(refresh).toHaveBeenCalled())
    expect(add).toHaveBeenCalledWith(
      expect.objectContaining({ type: "success", title: "Profile deleted" })
    )
  })

  it("renames a profile through the profile's own route", async () => {
    backendAnswers(200, { battletag: "PlayerTwo#5678" })
    renderWithProviders(<PlayerProfiles games={games} />, { user })

    await userEvent.click(screen.getByRole("button", { name: "Edit" }))
    const field = screen.getByLabelText("Battletag")
    await userEvent.clear(field)
    await userEvent.type(field, "PlayerTwo#5678")
    await userEvent.click(screen.getByRole("button", { name: "Save" }))

    await waitFor(() => expect(refresh).toHaveBeenCalled())
    expect(fetch).toHaveBeenCalledWith(
      `/api/players/${PLAYER_ID}`,
      expect.objectContaining({ method: "PATCH" })
    )
  })
})
