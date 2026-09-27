import { getServerApiClient } from "@/libs/api/client"
import { readIdParam, unknownResource } from "@/libs/api/route-params"
import { relayApiResult } from "@/libs/api/route-response"

interface FightContext {
  params: Promise<{ id: string }>
}

export async function POST(_request: Request, { params }: FightContext) {
  const id = await readIdParam(params, "id")
  if (!id) return unknownResource("Unknown fight")

  const client = await getServerApiClient()
  return relayApiResult(
    client.POST("/api/fights/{id}/results/confirmation", {
      params: { path: { id } },
    })
  )
}
