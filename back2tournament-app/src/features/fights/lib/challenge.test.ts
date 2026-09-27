import { describe, expect, it } from "vitest"
import type { PendingResult } from "../types"
import { challengeStage, formatLabel } from "./challenge"

const MINE = "11111111-1111-4111-8111-111111111111"
const THEIRS = "22222222-2222-4222-8222-222222222222"

function result(overrides: Partial<PendingResult>): PendingResult {
  return {
    competitor: { value: MINE },
    side: { competitor: { value: MINE }, type: "player", name: "Alpha#1" },
    status: "pending",
    declaredBy: null,
    ...overrides,
  }
}

describe("challengeStage", () => {
  it("asks for a declaration while nobody declared", () => {
    expect(challengeStage(result({}))).toBe("declare")
  })

  it("waits for the opponent once the caller's side declared", () => {
    expect(
      challengeStage(
        result({ status: "reporting", declaredBy: { value: MINE } })
      )
    ).toBe("awaiting")
  })

  it("asks the caller to confirm what the other side declared", () => {
    expect(
      challengeStage(
        result({ status: "reporting", declaredBy: { value: THEIRS } })
      )
    ).toBe("confirm")
  })
})

describe("formatLabel", () => {
  it("names a format by its players per side", () => {
    expect(formatLabel(1)).toBe("1v1")
    expect(formatLabel(5)).toBe("5v5")
  })

  it("reads a missing format as a duel", () => {
    expect(formatLabel(undefined)).toBe("1v1")
  })
})
