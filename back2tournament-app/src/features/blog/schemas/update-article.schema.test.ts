import { describe, expect, it } from "vitest"
import { changeArticleStatusSchema } from "./change-article-status.schema"
import { updateArticleSchema } from "./update-article.schema"

describe("updateArticleSchema", () => {
  it("keeps what is sent, trimmed, and leaves out the rest", () => {
    expect(updateArticleSchema.parse({ title: "  New title  " })).toEqual({
      title: "New title",
    })
  })

  it("lets both English fields be emptied, to remove the English version", () => {
    expect(updateArticleSchema.parse({ titleEn: "", bodyEn: " " })).toEqual({
      titleEn: "",
      bodyEn: "",
    })
  })

  it("does not judge an English field sent alone: the article holds the other", () => {
    expect(updateArticleSchema.safeParse({ titleEn: "Better" }).success).toBe(
      true
    )
  })

  it("flags an English version emptied by half", () => {
    expect(
      updateArticleSchema.safeParse({ titleEn: "Title", bodyEn: "" }).success
    ).toBe(false)
  })

  it("refuses to blank a field that is sent", () => {
    expect(updateArticleSchema.safeParse({ title: "   " }).success).toBe(false)
    expect(updateArticleSchema.safeParse({ body: "" }).success).toBe(false)
  })
})

describe("changeArticleStatusSchema", () => {
  it("knows the draft and published statuses only", () => {
    expect(changeArticleStatusSchema.parse({ status: "published" })).toEqual({
      status: "published",
    })
    expect(
      changeArticleStatusSchema.safeParse({ status: "archived" }).success
    ).toBe(false)
  })
})
