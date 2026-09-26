import { describe, expect, it } from "vitest"
import { isValidLocale, resolveLocale } from "./routing"

describe("i18n routing", () => {
  it("passes through a supported locale", () => {
    expect(resolveLocale("en")).toBe("en")
  })

  it("falls back to the default locale for unsupported values", () => {
    expect(resolveLocale("de")).toBe("fr")
    expect(resolveLocale(null)).toBe("fr")
    expect(resolveLocale(undefined)).toBe("fr")
  })

  it("validates locale strings", () => {
    expect(isValidLocale("fr")).toBe(true)
    expect(isValidLocale("xx")).toBe(false)
    expect(isValidLocale(42)).toBe(false)
  })
})
