import { describe, expect, it } from "vitest"
import { createArticleSchema } from "./create-article.schema"

const french = {
  title: "Résultats du week-end",
  categorySlug: "actualites",
  body: "Retour sur samedi",
}

function issuesOf(input: Record<string, unknown>) {
  const parsed = createArticleSchema.safeParse(input)
  return parsed.success
    ? []
    : parsed.error.issues.map((issue) => [issue.path.join("."), issue.message])
}

describe("createArticleSchema", () => {
  it("takes an article in French only", () => {
    expect(issuesOf(french)).toEqual([])
    expect(issuesOf({ ...french, titleEn: "  ", bodyEn: "" })).toEqual([])
  })

  it("takes a whole English version, trimmed", () => {
    expect(
      createArticleSchema.parse({
        ...french,
        titleEn: " Weekend results ",
        bodyEn: "Looking back at Saturday",
      })
    ).toMatchObject({
      titleEn: "Weekend results",
      bodyEn: "Looking back at Saturday",
    })
  })

  it("flags the missing half of an English version", () => {
    expect(issuesOf({ ...french, titleEn: "Weekend results" })).toEqual([
      ["bodyEn", "translationIncomplete"],
    ])
    expect(
      issuesOf({ ...french, titleEn: " ", bodyEn: "Looking back" })
    ).toEqual([["titleEn", "translationIncomplete"]])
  })
})
