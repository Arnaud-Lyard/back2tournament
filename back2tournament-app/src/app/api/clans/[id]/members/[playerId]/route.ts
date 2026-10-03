import { deleteClanMember } from "@/libs/api/generated/clan"
import { withSession } from "@/libs/api/session"
import { readIdParam, unknownResource } from "@/libs/api/route-params"
import { relayApiResult } from "@/libs/api/route-response"

interface MemberContext {
  params: Promise<{ id: string; playerId: string }>
}

export async function DELETE(_request: Request, { params }: MemberContext) {
  const id = await readIdParam(params, "id")
  const playerId = await readIdParam(params, "playerId")
  if (!id || !playerId) return unknownResource("Unknown clan membership")

  return relayApiResult(deleteClanMember(id, playerId, await withSession()))
}
