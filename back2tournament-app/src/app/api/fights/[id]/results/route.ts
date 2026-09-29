import { getServerApiClient } from "@/libs/api/client"
import { readIdParam, unknownResource } from "@/libs/api/route-params"
import {
  invalidInput,
  parseRequestBody,
  relayApiResult,
} from "@/libs/api/route-response"
import { declareResultsSchema } from "@/features/fights/schemas/declare-results.schema"

interface FightContext {
  params: Promise<{ id: string }>
}

export async function PATCH(request: Request, { params }: FightContext) {
  const id = await readIdParam(params, "id")
  if (!id) return unknownResource("Unknown fight")

  const parsed = await parseRequestBody(request, declareResultsSchema)
  if (!parsed.success) return invalidInput(parsed.error)

  const client = await getServerApiClient()
  return relayApiResult(
    client.PATCH("/api/user/fights/{id}/results", {
      params: { path: { id } },
      body: parsed.data,
    })
  )
}
