import { deleteUserAvatar, postUserAvatar } from "@/libs/api/generated/user"
import { withSession } from "@/libs/api/session"
import { readImageUpload } from "@/libs/api/route-image"
import { relayApiResult } from "@/libs/api/route-response"

export async function POST(request: Request) {
  const upload = await readImageUpload(request)
  if ("refusal" in upload) return upload.refusal

  return relayApiResult(
    postUserAvatar({ image: upload.image }, await withSession())
  )
}

export async function DELETE() {
  return relayApiResult(deleteUserAvatar(await withSession()))
}
