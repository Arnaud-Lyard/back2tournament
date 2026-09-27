import { getServerApiClient } from "@/libs/api/client"
import { readIdParam, unknownResource } from "@/libs/api/route-params"
import { relayApiResult } from "@/libs/api/route-response"

interface TeamContext {
  params: Promise<{ id: string }>
}

export async function DELETE(_request: Request, { params }: TeamContext) {
  const id = await readIdParam(params, "id")
  if (!id) return unknownResource("Unknown team")

  const client = await getServerApiClient()
  return relayApiResult(
    client.DELETE("/api/teams/{id}", { params: { path: { id } } })
  )
}
