import { deleteGameImage, postGameImage } from "@/libs/api/generated/game"
import { withSession } from "@/libs/api/session"
import { readImageUpload } from "@/libs/api/route-image"
import { readIdParam, unknownResource } from "@/libs/api/route-params"
import { relayApiResult } from "@/libs/api/route-response"

interface GameContext {
  params: Promise<{ id: string }>
}

export async function POST(request: Request, { params }: GameContext) {
  const id = await readIdParam(params, "id")
  if (!id) return unknownResource("Unknown game")

  const upload = await readImageUpload(request)
  if ("refusal" in upload) return upload.refusal

  return relayApiResult(
    postGameImage(id, { image: upload.image }, await withSession())
  )
}

export async function DELETE(_request: Request, { params }: GameContext) {
  const id = await readIdParam(params, "id")
  if (!id) return unknownResource("Unknown game")

  return relayApiResult(deleteGameImage(id, await withSession()))
}
