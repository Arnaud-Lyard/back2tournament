import { deleteTeam } from "@/libs/api/generated/team"
import { withSession } from "@/libs/api/session"
import { readIdParam, unknownResource } from "@/libs/api/route-params"
import { relayApiResult } from "@/libs/api/route-response"

interface TeamContext {
  params: Promise<{ id: string }>
}

export async function DELETE(_request: Request, { params }: TeamContext) {
  const id = await readIdParam(params, "id")
  if (!id) return unknownResource("Unknown team")

  return relayApiResult(deleteTeam(id, await withSession()))
}
