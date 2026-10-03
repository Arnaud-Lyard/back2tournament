import {
  deleteArticleImage,
  postArticleImage,
} from "@/libs/api/generated/article"
import { withSession } from "@/libs/api/session"
import { readImageUpload } from "@/libs/api/route-image"
import { readIdParam, unknownResource } from "@/libs/api/route-params"
import { relayApiResult } from "@/libs/api/route-response"

interface ArticleContext {
  params: Promise<{ id: string }>
}

export async function POST(request: Request, { params }: ArticleContext) {
  const id = await readIdParam(params, "id")
  if (!id) return unknownResource("Unknown article")

  const upload = await readImageUpload(request)
  if ("refusal" in upload) return upload.refusal

  return relayApiResult(
    postArticleImage(id, { image: upload.image }, await withSession())
  )
}

export async function DELETE(_request: Request, { params }: ArticleContext) {
  const id = await readIdParam(params, "id")
  if (!id) return unknownResource("Unknown article")

  return relayApiResult(deleteArticleImage(id, await withSession()))
}
