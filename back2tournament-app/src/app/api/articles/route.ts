import { getServerApiClient } from "@/libs/api/client"
import {
  invalidInput,
  parseRequestBody,
  relayApiResult,
} from "@/libs/api/route-response"
import { createArticleSchema } from "@/features/blog/schemas/create-article.schema"

export async function POST(request: Request) {
  const parsed = await parseRequestBody(request, createArticleSchema)
  if (!parsed.success) return invalidInput(parsed.error)

  const client = await getServerApiClient()
  return relayApiResult(client.POST("/api/articles/", { body: parsed.data }))
}
