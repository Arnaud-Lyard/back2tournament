import { postTournamentStart } from "@/libs/api/generated/tournament"
import { withSession } from "@/libs/api/session"
import { readIdParam, unknownResource } from "@/libs/api/route-params"
import { relayApiResult } from "@/libs/api/route-response"

interface TournamentContext {
  params: Promise<{ id: string }>
}

export async function POST(_request: Request, { params }: TournamentContext) {
  const id = await readIdParam(params, "id")
  if (!id) return unknownResource("Unknown tournament")

  return relayApiResult(postTournamentStart(id, await withSession()))
}
