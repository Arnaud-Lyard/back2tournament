import { describe, expect, it } from "vitest"
import type { TournamentMatchup } from "../types"
import { bracketRounds, roundName } from "./bracket"

describe("roundName", () => {
  it("names the last rounds by what is left to play", () => {
    expect(roundName(3, 3)).toEqual({ key: "final" })
    expect(roundName(2, 3)).toEqual({ key: "semiFinals" })
    expect(roundName(1, 3)).toEqual({ key: "quarterFinals" })
  })

  it("names earlier rounds by how many take part", () => {
    expect(roundName(1, 4)).toEqual({ key: "roundOf", count: 16 })
    expect(roundName(1, 5)).toEqual({ key: "roundOf", count: 32 })
  })
})

describe("bracketRounds", () => {
  it("groups matchups by round, top to bottom", () => {
    const matchup = (round: number, position: number): TournamentMatchup => ({
      id: { value: `${round}-${position}` },
      round,
      position,
    })

    const rounds = bracketRounds([matchup(2, 0), matchup(1, 1), matchup(1, 0)])

    expect(rounds.map((round) => round.map((one) => one.id?.value))).toEqual([
      ["1-0", "1-1"],
      ["2-0"],
    ])
  })
})
