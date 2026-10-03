import { postFightResultsConfirmation } from "@/libs/api/generated/fight"
import { withSession } from "@/libs/api/session"
import { readIdParam, unknownResource } from "@/libs/api/route-params"
import { relayApiResult } from "@/libs/api/route-response"

interface FightContext {
  params: Promise<{ id: string }>
}

export async function POST(_request: Request, { params }: FightContext) {
  const id = await readIdParam(params, "id")
  if (!id) return unknownResource("Unknown fight")

  return relayApiResult(postFightResultsConfirmation(id, await withSession()))
}
