import { describe, expect, it } from "vitest"
import type { CurrentUser } from "../types"
import { authUserFromJwt, toAuthUser } from "./to-auth-user"

const GAME_ID = "11111111-1111-4111-8111-111111111111"
const OTHER_GAME_ID = "22222222-2222-4222-8222-222222222222"

function me(players: CurrentUser["players"] = []): CurrentUser {
  return {
    id: "00000000-0000-4000-8000-000000000000",
    email: "demo@back2tournament.fr",
    username: "demo",
    roles: ["ROLE_EDITOR", "ROLE_USER"],
    verified: true,
    players,
  }
}

describe("toAuthUser", () => {
  it("files each player profile under its game", () => {
    const user = toAuthUser(
      me([
        { id: "p1", battletag: "Alpha#1234", game: GAME_ID },
        { id: "p2", battletag: "Bravo#5678", game: OTHER_GAME_ID },
      ])
    )

    expect(user.playersByGame).toEqual({
      [GAME_ID]: { id: "p1", battletag: "Alpha#1234" },
      [OTHER_GAME_ID]: { id: "p2", battletag: "Bravo#5678" },
    })
  })

  it("knows no profile in a game the user never joined", () => {
    expect(toAuthUser(me()).playersByGame[GAME_ID]).toBeUndefined()
  })

  it("maps the Symfony roles like the JWT does", () => {
    const user = toAuthUser(me())

    expect(user.username).toBe("demo")
    expect(user.role).toBe("editor")
    expect(user.permissions).toContain("backoffice:access")
  })
})

describe("authUserFromJwt", () => {
  it("keeps the identity but knows no player profile", () => {
    const user = authUserFromJwt("demo", ["ROLE_ADMIN"])

    expect(user.role).toBe("admin")
    expect(user.playersByGame).toEqual({})
  })
})
