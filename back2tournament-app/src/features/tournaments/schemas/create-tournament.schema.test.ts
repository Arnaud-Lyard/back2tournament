import { describe, expect, it } from "vitest"
import { createTournamentSchema } from "./create-tournament.schema"

const GAME = "11111111-1111-4111-8111-111111111111"

function tournament(overrides: Record<string, unknown> = {}) {
  return {
    name: "Autumn Cup",
    game: GAME,
    teamSize: 1,
    capacity: 8,
    startsAt: "2999-10-01T18:30:00+02:00",
    ...overrides,
  }
}

describe("createTournamentSchema", () => {
  it("accepts a tournament to come, dated with its offset", () => {
    expect(createTournamentSchema.safeParse(tournament()).success).toBe(true)
  })

  it("refuses a start in the past", () => {
    const parsed = createTournamentSchema.safeParse(
      tournament({ startsAt: "2000-01-01T00:00:00+00:00" })
    )

    expect(parsed.error?.issues.map((issue) => issue.message)).toContain(
      "invalidDate"
    )
  })

  it("refuses a date without its offset", () => {
    expect(
      createTournamentSchema.safeParse(
        tournament({ startsAt: "2999-10-01T18:30" })
      ).success
    ).toBe(false)
  })

  it("holds between 2 and 128 participants", () => {
    for (const capacity of [1, 129, 2.5]) {
      const parsed = createTournamentSchema.safeParse(tournament({ capacity }))
      expect(parsed.error?.issues.map((issue) => issue.message)).toContain(
        "invalidCapacity"
      )
    }
  })
})
