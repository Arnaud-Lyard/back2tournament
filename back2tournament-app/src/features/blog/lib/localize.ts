import type { ArticleSummary } from "../types"

export interface LocalizedArticle {
  title: string
  body: string
  lang: "fr" | "en"
  untranslated: boolean
}

export function localizeArticle(
  article: Pick<ArticleSummary, "title" | "body" | "titleEn" | "bodyEn">,
  locale: string
): LocalizedArticle {
  if (locale === "en" && article.titleEn && article.bodyEn) {
    return {
      title: article.titleEn,
      body: article.bodyEn,
      lang: "en",
      untranslated: false,
    }
  }

  return {
    title: article.title ?? "",
    body: article.body ?? "",
    lang: "fr",
    untranslated: locale === "en",
  }
}
