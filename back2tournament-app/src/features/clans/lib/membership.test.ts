import { describe, expect, it } from "vitest"
import type { ClanDetail, MyClan } from "../types"
import { activeClanIn, teamFormats, teamsLedBy } from "./membership"

const GAME = "11111111-1111-4111-8111-111111111111"
const OTHER_GAME = "22222222-2222-4222-8222-222222222222"
const ME = "33333333-3333-4333-8333-333333333333"

function place(
  game: string,
  status: "invited" | "requested" | "active",
  name: string
): MyClan {
  return {
    clan: { id: { value: name }, name, game: { value: game } },
    membership: { status, role: "member" },
    requests: 0,
  }
}

describe("activeClanIn", () => {
  it("finds the clan the caller is an active member of in that game", () => {
    const clans = [
      place(OTHER_GAME, "active", "other"),
      place(GAME, "invited", "invited"),
      place(GAME, "active", "mine"),
    ]

    expect(activeClanIn(clans, GAME)?.clan.name).toBe("mine")
  })

  it("does not take a pending invitation for a membership", () => {
    expect(
      activeClanIn([place(GAME, "invited", "invited")], GAME)
    ).toBeUndefined()
  })

  it("does not take a request to join for a membership", () => {
    expect(
      activeClanIn([place(GAME, "requested", "asked")], GAME)
    ).toBeUndefined()
  })
})

describe("teamsLedBy", () => {
  it("keeps the teams the player leads", () => {
    const clan: ClanDetail = {
      teams: [
        { id: { value: "led" }, leader: { value: ME }, size: 2 },
        { id: { value: "not-led" }, leader: { value: OTHER_GAME }, size: 2 },
      ],
    }

    expect(teamsLedBy(clan, ME).map((team) => team.id?.value)).toEqual(["led"])
    expect(teamsLedBy(clan, undefined)).toEqual([])
  })
})

describe("teamFormats", () => {
  it("keeps the formats above 1v1", () => {
    expect(teamFormats([1, 2, 5])).toEqual([2, 5])
    expect(teamFormats(undefined)).toEqual([])
  })
})
