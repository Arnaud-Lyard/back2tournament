import { postClanMember } from "@/libs/api/generated/clan"
import { withSession } from "@/libs/api/session"
import { readIdParam, unknownResource } from "@/libs/api/route-params"
import { relayApiResult } from "@/libs/api/route-response"

interface ClanContext {
  params: Promise<{ id: string }>
}

export async function POST(_request: Request, { params }: ClanContext) {
  const id = await readIdParam(params, "id")
  if (!id) return unknownResource("Unknown clan")

  return relayApiResult(postClanMember(id, await withSession()))
}
