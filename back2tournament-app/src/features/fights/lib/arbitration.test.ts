import { describe, expect, it } from "vitest"
import { readFightStatusFilter } from "./arbitration"

describe("readFightStatusFilter", () => {
  it("lists the fights waiting for a confirmation by default", () => {
    expect(readFightStatusFilter(undefined)).toBe("reporting")
    expect(readFightStatusFilter("disputed")).toBe("reporting")
  })

  it("keeps a status the backoffice knows", () => {
    expect(readFightStatusFilter("pending")).toBe("pending")
    expect(readFightStatusFilter(["finished", "all"])).toBe("finished")
    expect(readFightStatusFilter("all")).toBe("all")
  })
})
