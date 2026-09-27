import type { ArticleSummary } from "../types"

export interface LocalizedArticle {
  title: string
  body: string
  /** The language the title and the body are written in. */
  lang: "fr" | "en"
  /** The site is read in English, but the article is in French only. */
  untranslated: boolean
}

/**
 * The version of an article to show in the site's language: the English one
 * when the site is read in English and the article has it, the French one
 * otherwise.
 */
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
