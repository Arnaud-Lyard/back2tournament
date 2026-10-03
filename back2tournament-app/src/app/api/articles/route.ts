import { postArticle } from "@/libs/api/generated/article"
import { withSession } from "@/libs/api/session"
import {
  invalidInput,
  parseRequestBody,
  relayApiResult,
} from "@/libs/api/route-response"
import { createArticleSchema } from "@/features/blog/schemas/create-article.schema"

export async function POST(request: Request) {
  const parsed = await parseRequestBody(request, createArticleSchema)
  if (!parsed.success) return invalidInput(parsed.error)

  return relayApiResult(postArticle(parsed.data, await withSession()))
}
