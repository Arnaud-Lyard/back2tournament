import { getServerApiClient } from "@/libs/api/client"
import { readIdParam, unknownResource } from "@/libs/api/route-params"
import {
  invalidInput,
  parseRequestBody,
  relayApiResult,
} from "@/libs/api/route-response"
import { updateArticleSchema } from "@/features/blog/schemas/update-article.schema"

interface ArticleContext {
  params: Promise<{ id: string }>
}

export async function PATCH(request: Request, { params }: ArticleContext) {
  const id = await readIdParam(params, "id")
  if (!id) return unknownResource("Unknown article")

  const parsed = await parseRequestBody(request, updateArticleSchema)
  if (!parsed.success) return invalidInput(parsed.error)

  const client = await getServerApiClient()
  return relayApiResult(
    client.PATCH("/api/editor/articles/{id}", {
      params: { path: { id } },
      body: parsed.data,
    })
  )
}
