import type { ArticleSummary } from "../types"

/** Only a published article is on the public blog, and takes comments. */
export function isPublished(article: Pick<ArticleSummary, "status">): boolean {
  return article.status === "published"
}

/**
 * The date an article goes by: the day it was published, or for a draft
 * the day it was written.
 */
export function articleDate(
  article: Pick<ArticleSummary, "publishedAt" | "createdAt">
): string | undefined {
  return article.publishedAt ?? article.createdAt
}
