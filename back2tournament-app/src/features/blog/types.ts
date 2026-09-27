import type { components, paths } from "@/libs/api/schema"

export type Category = components["schemas"]["Category"]

export type ArticleSummary = components["schemas"]["Article"]

/** `draft` until an editor publishes it: only then is it on the public blog. */
export type ArticleStatus = NonNullable<ArticleSummary["status"]>

/** Which articles a list asks for: the public blog, the drafts, or both. */
export type ArticleStatusFilter = ArticleStatus | "all"

export type CreatedCategory =
  paths["/api/categories/"]["post"]["responses"][200]["content"]["application/json"]

export type CreatedArticle =
  paths["/api/articles/"]["post"]["responses"][200]["content"]["application/json"]

export type CreatedComment =
  paths["/api/comments/"]["post"]["responses"][200]["content"]["application/json"]

export const ARTICLES_PER_PAGE = 6
