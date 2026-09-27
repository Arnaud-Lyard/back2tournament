import { getServerApiClient } from "@/libs/api/client"
import { readImageUpload } from "@/libs/api/route-image"
import { relayApiResult } from "@/libs/api/route-response"
import { toImageForm } from "@/features/images/lib/image-file"

export async function POST(request: Request) {
  const upload = await readImageUpload(request)
  if ("refusal" in upload) return upload.refusal

  const client = await getServerApiClient()
  return relayApiResult(
    client.POST("/api/users/me/avatar", {
      body: { image: upload.image },
      bodySerializer: toImageForm,
    })
  )
}

export async function DELETE() {
  const client = await getServerApiClient()
  return relayApiResult(client.DELETE("/api/users/me/avatar"))
}
