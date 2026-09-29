import { getServerApiClient } from "@/libs/api/client"
import { readIdParam, unknownResource } from "@/libs/api/route-params"
import {
  invalidInput,
  parseRequestBody,
  relayApiResult,
} from "@/libs/api/route-response"
import { invitePlayerSchema } from "@/features/clans/schemas/invite-player.schema"

interface ClanContext {
  params: Promise<{ id: string }>
}

export async function POST(request: Request, { params }: ClanContext) {
  const id = await readIdParam(params, "id")
  if (!id) return unknownResource("Unknown clan")

  const parsed = await parseRequestBody(request, invitePlayerSchema)
  if (!parsed.success) return invalidInput(parsed.error)

  const client = await getServerApiClient()
  return relayApiResult(
    client.POST("/api/user/clans/{id}/invitations", {
      params: { path: { id } },
      body: parsed.data,
    })
  )
}
