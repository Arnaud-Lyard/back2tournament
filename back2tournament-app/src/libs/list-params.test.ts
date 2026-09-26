import { describe, expect, it } from "vitest"
import {
  listHref,
  pageWindow,
  readPageParam,
  readSearchParam,
  readSlugParam,
} from "./list-params"

describe("readPageParam", () => {
  it("reads a page number", () => {
    expect(readPageParam("3")).toBe(3)
  })

  it("falls back to the first page when there is no page", () => {
    expect(readPageParam(undefined)).toBe(1)
    expect(readPageParam("")).toBe(1)
  })

  it("falls back to the first page rather than refusing a bad one", () => {
    expect(readPageParam("two")).toBe(1)
    expect(readPageParam("-2")).toBe(1)
    expect(readPageParam("0")).toBe(1)
  })

  it("reads the first value when the param is repeated", () => {
    expect(readPageParam(["2", "9"])).toBe(2)
  })
})

describe("readSearchParam", () => {
  it("trims what the visitor typed", () => {
    expect(readSearchParam("  alpha ")).toBe("alpha")
  })

  it("is empty when nothing was searched", () => {
    expect(readSearchParam(undefined)).toBe("")
  })

  it("caps the term so the URL cannot grow unbounded", () => {
    expect(readSearchParam("a".repeat(500))).toHaveLength(100)
  })
})

describe("readSlugParam", () => {
  it("keeps a slug", () => {
    expect(readSlugParam("news-2026")).toBe("news-2026")
  })

  it("ignores anything that is not one", () => {
    expect(readSlugParam("Some Category!")).toBe("")
    expect(readSlugParam(undefined)).toBe("")
  })
})

describe("listHref", () => {
  it("keeps the path alone when nothing is set", () => {
    expect(listHref("/blog", { page: 1, q: "" })).toBe("/blog")
  })

  it("carries every set parameter", () => {
    expect(listHref("/blog", { page: 2, q: "alpha", category: "news" })).toBe(
      "/blog?page=2&q=alpha&category=news"
    )
  })

  it("drops the first page, so it is never in a URL", () => {
    expect(listHref("/blog", { page: 1, q: "alpha" })).toBe("/blog?q=alpha")
  })

  it("escapes what the visitor typed", () => {
    expect(listHref("/blog", { q: "a&b c" })).toBe("/blog?q=a%26b+c")
  })
})

describe("pageWindow", () => {
  it("shows every page of a short list", () => {
    expect(pageWindow(1, 3)).toEqual([1, 2, 3])
  })

  it("has nothing to show when there is no page", () => {
    expect(pageWindow(1, 0)).toEqual([])
  })

  it("keeps the current page in the middle", () => {
    expect(pageWindow(7, 20)).toEqual([5, 6, 7, 8, 9])
  })

  it("stops sliding at the first page", () => {
    expect(pageWindow(1, 20)).toEqual([1, 2, 3, 4, 5])
  })

  it("stops sliding at the last page", () => {
    expect(pageWindow(20, 20)).toEqual([16, 17, 18, 19, 20])
  })
})
