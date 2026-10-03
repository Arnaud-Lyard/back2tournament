import { patchArticleStatus } from "@/libs/api/generated/article"
import { withSession } from "@/libs/api/session"
import { readIdParam, unknownResource } from "@/libs/api/route-params"
import {
  invalidInput,
  parseRequestBody,
  relayApiResult,
} from "@/libs/api/route-response"
import { changeArticleStatusSchema } from "@/features/blog/schemas/change-article-status.schema"

interface ArticleContext {
  params: Promise<{ id: string }>
}

export async function PATCH(request: Request, { params }: ArticleContext) {
  const id = await readIdParam(params, "id")
  if (!id) return unknownResource("Unknown article")

  const parsed = await parseRequestBody(request, changeArticleStatusSchema)
  if (!parsed.success) return invalidInput(parsed.error)

  return relayApiResult(
    patchArticleStatus(id, parsed.data, await withSession())
  )
}
