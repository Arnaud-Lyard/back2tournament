import { postCategory } from "@/libs/api/generated/category"
import { withSession } from "@/libs/api/session"
import {
  invalidInput,
  parseRequestBody,
  relayApiResult,
} from "@/libs/api/route-response"
import { createCategorySchema } from "@/features/blog/schemas/create-category.schema"

export async function POST(request: Request) {
  const parsed = await parseRequestBody(request, createCategorySchema)
  if (!parsed.success) return invalidInput(parsed.error)

  return relayApiResult(postCategory(parsed.data, await withSession()))
}
