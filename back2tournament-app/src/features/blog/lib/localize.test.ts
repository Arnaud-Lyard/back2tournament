import { describe, expect, it } from "vitest"
import { localizeArticle } from "./localize"

const translated = {
  title: "Résultats du week-end",
  body: "Retour sur samedi",
  titleEn: "Weekend results",
  bodyEn: "Looking back at Saturday",
}

describe("localizeArticle", () => {
  it("reads the French version on the French site", () => {
    expect(localizeArticle(translated, "fr")).toEqual({
      title: "Résultats du week-end",
      body: "Retour sur samedi",
      lang: "fr",
      untranslated: false,
    })
  })

  it("reads the English version on the English site", () => {
    expect(localizeArticle(translated, "en")).toEqual({
      title: "Weekend results",
      body: "Looking back at Saturday",
      lang: "en",
      untranslated: false,
    })
  })

  it("falls back on the French version when the article has no English one", () => {
    expect(
      localizeArticle({ ...translated, titleEn: null, bodyEn: null }, "en")
    ).toEqual({
      title: "Résultats du week-end",
      body: "Retour sur samedi",
      lang: "fr",
      untranslated: true,
    })
  })
})
