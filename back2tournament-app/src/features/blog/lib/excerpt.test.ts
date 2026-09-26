import { describe, expect, it } from "vitest"
import { toExcerpt } from "./excerpt"

describe("toExcerpt", () => {
  it("returns a short article whole, with no ellipsis", () => {
    expect(toExcerpt("A short one.")).toBe("A short one.")
  })

  it("has nothing to show for an article without a body", () => {
    expect(toExcerpt(undefined)).toBe("")
    expect(toExcerpt(null)).toBe("")
  })

  it("collapses the line breaks a body carries", () => {
    expect(toExcerpt("Two\n\nparagraphs")).toBe("Two paragraphs")
  })

  it("cuts on a word boundary and says there is more", () => {
    expect(toExcerpt("alpha bravo charlie delta", 14)).toBe("alpha bravo…")
  })

  it("cuts inside a single long word rather than showing nothing", () => {
    expect(toExcerpt("abcdefghijklmnop", 8)).toBe("abcdefgh…")
  })
})
