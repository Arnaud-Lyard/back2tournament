import { getServerApiClient } from "@/libs/api/client"
import { readIdParam, unknownResource } from "@/libs/api/route-params"
import { relayApiResult } from "@/libs/api/route-response"

interface MemberContext {
  params: Promise<{ id: string; playerId: string }>
}

export async function DELETE(_request: Request, { params }: MemberContext) {
  const id = await readIdParam(params, "id")
  const playerId = await readIdParam(params, "playerId")
  if (!id || !playerId) return unknownResource("Unknown clan membership")

  const client = await getServerApiClient()
  return relayApiResult(
    client.DELETE("/api/clans/{id}/members/{playerid}", {
      params: { path: { id, playerid: playerId } },
    })
  )
}
