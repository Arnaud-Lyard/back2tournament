import { describe, expect, it } from "vitest"
import { createTeamSchema } from "./create-team.schema"

const CLAN = "11111111-1111-4111-8111-111111111111"
const ONE = "22222222-2222-4222-8222-222222222222"
const TWO = "33333333-3333-4333-8333-333333333333"
const THREE = "44444444-4444-4444-8444-444444444444"

describe("createTeamSchema", () => {
  it("accepts a lineup of exactly the format's size, its captain in it", () => {
    expect(
      createTeamSchema.safeParse({
        clan: CLAN,
        name: "Duo",
        size: 2,
        players: [ONE, TWO],
        leader: ONE,
      }).success
    ).toBe(true)
  })

  it("refuses a lineup of another size", () => {
    const parsed = createTeamSchema.safeParse({
      clan: CLAN,
      name: "Duo",
      size: 2,
      players: [ONE, TWO, THREE],
      leader: ONE,
    })

    expect(parsed.success).toBe(false)
    expect(parsed.error?.issues.map((issue) => issue.message)).toContain(
      "lineupSize"
    )
  })

  it("refuses a captain who does not play", () => {
    const parsed = createTeamSchema.safeParse({
      clan: CLAN,
      name: "Duo",
      size: 2,
      players: [ONE, TWO],
      leader: THREE,
    })

    expect(parsed.error?.issues.map((issue) => issue.message)).toContain(
      "leaderInLineup"
    )
  })
})
