import { getServerApiClient } from "@/libs/api/client"
import {
  invalidInput,
  parseRequestBody,
  relayApiResult,
} from "@/libs/api/route-response"
import { createCategorySchema } from "@/features/blog/schemas/create-category.schema"

export async function POST(request: Request) {
  const parsed = await parseRequestBody(request, createCategorySchema)
  if (!parsed.success) return invalidInput(parsed.error)

  const client = await getServerApiClient()
  return relayApiResult(
    client.POST("/api/admin/categories/", { body: parsed.data })
  )
}
