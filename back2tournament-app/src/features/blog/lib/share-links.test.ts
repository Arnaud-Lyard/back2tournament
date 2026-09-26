import { describe, expect, it } from "vitest"
import { shareLink } from "./share-links"

const url = "https://back2tournament.test/blog/1"
const title = "Spring & summer #1"

describe("shareLink", () => {
  it("points Facebook at the page", () => {
    expect(shareLink("facebook", url, title)).toBe(
      "https://www.facebook.com/sharer/sharer.php?u=https%3A%2F%2Fback2tournament.test%2Fblog%2F1"
    )
  })

  it("points LinkedIn at the page", () => {
    expect(shareLink("linkedin", url, title)).toContain(
      "linkedin.com/sharing/share-offsite/?url=https%3A%2F%2F"
    )
  })

  it("carries the title to the tweet, escaped", () => {
    expect(shareLink("twitter", url, title)).toContain(
      "&text=Spring%20%26%20summer%20%231"
    )
  })
})
