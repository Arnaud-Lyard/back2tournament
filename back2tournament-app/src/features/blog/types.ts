import type {
  Article,
  Category,
  Comment,
  PostCategory200,
} from "@/libs/api/generated/endpoints.schemas"

export type { Category }

export type ArticleSummary = Article

export type ArticleStatus = NonNullable<ArticleSummary["status"]>

export type ArticleStatusFilter = ArticleStatus | "all"

export type CreatedCategory = PostCategory200

export type CreatedArticle = Article

export type CreatedComment = Comment

export const ARTICLES_PER_PAGE = 6
