import { postComment } from "@/libs/api/generated/comment"
import { withSession } from "@/libs/api/session"
import {
  invalidInput,
  parseRequestBody,
  relayApiResult,
} from "@/libs/api/route-response"
import { createCommentSchema } from "@/features/blog/schemas/create-comment.schema"

export async function POST(request: Request) {
  const parsed = await parseRequestBody(request, createCommentSchema)
  if (!parsed.success) return invalidInput(parsed.error)

  return relayApiResult(postComment(parsed.data, await withSession()))
}
