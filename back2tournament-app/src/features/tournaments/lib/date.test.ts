import { afterEach, describe, expect, it, vi } from "vitest"
import { toIsoWithOffset } from "./date"

afterEach(() => {
  vi.restoreAllMocks()
})

describe("toIsoWithOffset", () => {
  it("sends the wall-clock time with the visitor's offset", () => {
    vi.spyOn(Date.prototype, "getTimezoneOffset").mockReturnValue(-120)

    expect(toIsoWithOffset("2026-10-01T18:30")).toBe(
      "2026-10-01T18:30:00+02:00"
    )
  })

  it("writes a negative offset west of Greenwich", () => {
    vi.spyOn(Date.prototype, "getTimezoneOffset").mockReturnValue(270)

    expect(toIsoWithOffset("2026-10-01T18:30")).toBe(
      "2026-10-01T18:30:00-04:30"
    )
  })

  it("reads an empty or broken value as no date", () => {
    expect(toIsoWithOffset("")).toBe("")
    expect(toIsoWithOffset("not a date")).toBe("")
  })
})
