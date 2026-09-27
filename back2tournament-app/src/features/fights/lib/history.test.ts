import { describe, expect, it } from "vitest"
import type { SettledResult } from "../types"
import { opponentHref } from "./history"

const GAME = "11111111-1111-4111-8111-111111111111"
const RIVAL = "22222222-2222-4222-8222-222222222222"

function result(opponent: SettledResult["opponent"]): SettledResult {
  return {
    fight: { value: "33333333-3333-4333-8333-333333333333" },
    game: { value: GAME },
    teamSize: 1,
    outcome: "win",
    side: {
      competitor: { value: "44444444-4444-4444-8444-444444444444" },
      type: "player",
      name: "Demo#1000",
      score: 3,
    },
    opponent,
  }
}

describe("opponentHref", () => {
  it("links a duel opponent to its profile in the game", () => {
    expect(
      opponentHref(
        result({
          competitor: { value: RIVAL },
          type: "player",
          reference: { value: RIVAL },
          name: "Raven#1003",
          score: 1,
        })
      )
    ).toBe(`/games/${GAME}/players/${RIVAL}`)
  })

  it("links no team, which has no page of its own", () => {
    expect(
      opponentHref(
        result({
          competitor: { value: RIVAL },
          type: "team",
          reference: { value: RIVAL },
          name: "Rival 2v2",
          score: 1,
        })
      )
    ).toBeNull()
  })

  it("links nothing when the other side is unknown", () => {
    expect(opponentHref(result(null))).toBeNull()
    expect(
      opponentHref(
        result({
          competitor: { value: RIVAL },
          type: null,
          reference: null,
          name: null,
          score: 0,
        })
      )
    ).toBeNull()
  })
})
