import { describe, expect, it } from "vitest"
import { rankedSubjectHref, rankingHref, readRankingView } from "./ranking"

const GAME_ID = "11111111-1111-4111-8111-111111111111"
const SUBJECT_ID = "22222222-2222-4222-8222-222222222222"

describe("readRankingView", () => {
  it("shows the clans only when they are asked for", () => {
    expect(readRankingView("clans")).toBe("clans")
    expect(readRankingView(["clans", "players"])).toBe("clans")
    expect(readRankingView("players")).toBe("players")
    expect(readRankingView("teams")).toBe("players")
    expect(readRankingView(undefined)).toBe("players")
  })
})

describe("rankedSubjectHref", () => {
  it("leads a player profile to its page, a clan to its own", () => {
    expect(
      rankedSubjectHref(GAME_ID, { type: "player", id: { value: SUBJECT_ID } })
    ).toBe(`/games/${GAME_ID}/players/${SUBJECT_ID}`)
    expect(
      rankedSubjectHref(GAME_ID, { type: "clan", id: { value: SUBJECT_ID } })
    ).toBe(`/games/${GAME_ID}/clans/${SUBJECT_ID}`)
  })

  it("leads nowhere without an id", () => {
    expect(rankedSubjectHref(GAME_ID, { type: "clan" })).toBeNull()
    expect(rankedSubjectHref(GAME_ID, undefined)).toBeNull()
  })
})

describe("rankingHref", () => {
  it("opens the ranking on the view asked for", () => {
    expect(rankingHref(GAME_ID, "players")).toBe(`/games/${GAME_ID}/rankings`)
    expect(rankingHref(GAME_ID, "clans")).toBe(
      `/games/${GAME_ID}/rankings?view=clans`
    )
  })
})
