import { fetchJson } from "@/libs/api/fetch-json"
import type { ChangeArticleStatusInput } from "../schemas/change-article-status.schema"
import type { CreateArticleInput } from "../schemas/create-article.schema"
import type { CreateCategoryInput } from "../schemas/create-category.schema"
import type { CreateCommentInput } from "../schemas/create-comment.schema"
import type { UpdateArticleInput } from "../schemas/update-article.schema"
import type {
  ArticleSummary,
  CreatedArticle,
  CreatedCategory,
  CreatedComment,
} from "../types"

export interface UpdateArticleVariables extends UpdateArticleInput {
  id: string
}

export interface ChangeArticleStatusVariables extends ChangeArticleStatusInput {
  id: string
}

export function createCategory(
  input: CreateCategoryInput
): Promise<CreatedCategory> {
  return fetchJson<CreatedCategory>("/api/categories", {
    method: "POST",
    body: input,
  })
}

export function createArticle(
  input: CreateArticleInput
): Promise<CreatedArticle> {
  return fetchJson<CreatedArticle>("/api/articles", {
    method: "POST",
    body: input,
  })
}

export function updateArticle({
  id,
  ...input
}: UpdateArticleVariables): Promise<ArticleSummary> {
  return fetchJson<ArticleSummary>(`/api/articles/${encodeURIComponent(id)}`, {
    method: "PATCH",
    body: input,
  })
}

export function changeArticleStatus({
  id,
  ...input
}: ChangeArticleStatusVariables): Promise<ArticleSummary> {
  return fetchJson<ArticleSummary>(
    `/api/articles/${encodeURIComponent(id)}/status`,
    { method: "PATCH", body: input }
  )
}

export function createComment(
  input: CreateCommentInput
): Promise<CreatedComment> {
  return fetchJson<CreatedComment>("/api/comments", {
    method: "POST",
    body: input,
  })
}
