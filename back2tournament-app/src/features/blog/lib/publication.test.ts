import { describe, expect, it } from "vitest"
import { articleDate, isPublished } from "./publication"

describe("isPublished", () => {
  it("is true for a published article only", () => {
    expect(isPublished({ status: "published" })).toBe(true)
    expect(isPublished({ status: "draft" })).toBe(false)
    expect(isPublished({})).toBe(false)
  })
})

describe("articleDate", () => {
  it("dates a published article by its publication", () => {
    expect(
      articleDate({
        createdAt: "2026-09-01T10:00:00+00:00",
        publishedAt: "2026-09-20T08:30:00+00:00",
      })
    ).toBe("2026-09-20T08:30:00+00:00")
  })

  it("dates a draft by the day it was written", () => {
    expect(
      articleDate({ createdAt: "2026-09-01T10:00:00+00:00", publishedAt: null })
    ).toBe("2026-09-01T10:00:00+00:00")
  })
})
