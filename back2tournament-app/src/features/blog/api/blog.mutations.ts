import { fetchJson } from "@/libs/api/fetch-json"
import type { CreateArticleInput } from "../schemas/create-article.schema"
import type { CreateCategoryInput } from "../schemas/create-category.schema"
import type { CreateCommentInput } from "../schemas/create-comment.schema"
import type { CreatedArticle, CreatedCategory, CreatedComment } from "../types"

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

export function createComment(
  input: CreateCommentInput
): Promise<CreatedComment> {
  return fetchJson<CreatedComment>("/api/comments", {
    method: "POST",
    body: input,
  })
}
