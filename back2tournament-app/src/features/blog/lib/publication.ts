import type { ArticleSummary } from "../types"

export function isPublished(article: Pick<ArticleSummary, "status">): boolean {
  return article.status === "published"
}

export function articleDate(
  article: Pick<ArticleSummary, "publishedAt" | "createdAt">
): string | undefined {
  return article.publishedAt ?? article.createdAt
}
