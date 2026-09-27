import { getServerApiClient } from "@/libs/api/client"
import { readImageUpload } from "@/libs/api/route-image"
import { readIdParam, unknownResource } from "@/libs/api/route-params"
import { relayApiResult } from "@/libs/api/route-response"
import { toImageForm } from "@/features/images/lib/image-file"

interface GameContext {
  params: Promise<{ id: string }>
}

export async function POST(request: Request, { params }: GameContext) {
  const id = await readIdParam(params, "id")
  if (!id) return unknownResource("Unknown game")

  const upload = await readImageUpload(request)
  if ("refusal" in upload) return upload.refusal

  const client = await getServerApiClient()
  return relayApiResult(
    client.POST("/api/games/{id}/image", {
      params: { path: { id } },
      body: { image: upload.image },
      bodySerializer: toImageForm,
    })
  )
}

export async function DELETE(_request: Request, { params }: GameContext) {
  const id = await readIdParam(params, "id")
  if (!id) return unknownResource("Unknown game")

  const client = await getServerApiClient()
  return relayApiResult(
    client.DELETE("/api/games/{id}/image", { params: { path: { id } } })
  )
}
