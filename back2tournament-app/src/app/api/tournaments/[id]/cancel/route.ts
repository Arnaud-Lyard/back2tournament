import { getServerApiClient } from "@/libs/api/client"
import { readIdParam, unknownResource } from "@/libs/api/route-params"
import { relayApiResult } from "@/libs/api/route-response"

interface TournamentContext {
  params: Promise<{ id: string }>
}

export async function POST(_request: Request, { params }: TournamentContext) {
  const id = await readIdParam(params, "id")
  if (!id) return unknownResource("Unknown tournament")

  const client = await getServerApiClient()
  return relayApiResult(
    client.POST("/api/user/tournaments/{id}/cancel", {
      params: { path: { id } },
    })
  )
}
