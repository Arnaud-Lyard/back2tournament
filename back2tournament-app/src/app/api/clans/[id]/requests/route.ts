import { getServerApiClient } from "@/libs/api/client"
import { readIdParam, unknownResource } from "@/libs/api/route-params"
import { relayApiResult } from "@/libs/api/route-response"

interface ClanContext {
  params: Promise<{ id: string }>
}

export async function POST(_request: Request, { params }: ClanContext) {
  const id = await readIdParam(params, "id")
  if (!id) return unknownResource("Unknown clan")

  const client = await getServerApiClient()
  return relayApiResult(
    client.POST("/api/user/clans/{id}/requests", { params: { path: { id } } })
  )
}
