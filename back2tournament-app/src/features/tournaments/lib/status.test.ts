import { describe, expect, it } from "vitest"
import { isValidStatus } from "./status"

describe("isValidStatus", () => {
  it("accepts the states a tournament goes through", () => {
    for (const status of ["upcoming", "ongoing", "finished", "cancelled"]) {
      expect(isValidStatus(status)).toBe(true)
    }
  })

  it("refuses anything else, such as a crafted query string", () => {
    expect(isValidStatus("draft")).toBe(false)
    expect(isValidStatus("")).toBe(false)
  })
})
