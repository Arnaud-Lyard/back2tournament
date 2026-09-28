import { getServerApiClient } from "@/libs/api/client"
import { readImageUpload } from "@/libs/api/route-image"
import { readIdParam, unknownResource } from "@/libs/api/route-params"
import { relayApiResult } from "@/libs/api/route-response"
import { toImageForm } from "@/features/images/lib/image-file"

interface ArticleContext {
  params: Promise<{ id: string }>
}

export async function POST(request: Request, { params }: ArticleContext) {
  const id = await readIdParam(params, "id")
  if (!id) return unknownResource("Unknown article")

  const upload = await readImageUpload(request)
  if ("refusal" in upload) return upload.refusal

  const client = await getServerApiClient()
  return relayApiResult(
    client.POST("/api/editor/articles/{id}/image", {
      params: { path: { id } },
      body: { image: upload.image },
      bodySerializer: toImageForm,
    })
  )
}

export async function DELETE(_request: Request, { params }: ArticleContext) {
  const id = await readIdParam(params, "id")
  if (!id) return unknownResource("Unknown article")

  const client = await getServerApiClient()
  return relayApiResult(
    client.DELETE("/api/editor/articles/{id}/image", {
      params: { path: { id } },
    })
  )
}
