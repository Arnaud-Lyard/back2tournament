import { getServerApiClient } from "@/libs/api/client"
import {
  invalidInput,
  parseRequestBody,
  relayApiResult,
} from "@/libs/api/route-response"
import { createCommentSchema } from "@/features/blog/schemas/create-comment.schema"

export async function POST(request: Request) {
  const parsed = await parseRequestBody(request, createCommentSchema)
  if (!parsed.success) return invalidInput(parsed.error)

  const client = await getServerApiClient()
  return relayApiResult(
    client.POST("/api/user/comments/", { body: parsed.data })
  )
}
