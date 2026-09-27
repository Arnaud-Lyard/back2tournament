import { describe, expect, it } from "vitest"
import { parseTeamSizes } from "./team-sizes"

describe("parseTeamSizes", () => {
  it("reads formats typed as numbers, once each and in order", () => {
    expect(parseTeamSizes("5, 1, 2, 1")).toEqual([1, 2, 5])
  })

  it("reads formats typed as players name them", () => {
    expect(parseTeamSizes("1v1 3v3 5V5")).toEqual([1, 3, 5])
  })

  it("reads a list holding anything else as none", () => {
    expect(parseTeamSizes("1, two")).toEqual([])
    expect(parseTeamSizes("2v3")).toEqual([])
    expect(parseTeamSizes("")).toEqual([])
  })
})
