import { patchFightStatus } from "@/libs/api/generated/fight"
import { withSession } from "@/libs/api/session"
import { readIdParam, unknownResource } from "@/libs/api/route-params"
import {
  invalidInput,
  parseRequestBody,
  relayApiResult,
} from "@/libs/api/route-response"
import { changeFightStatusSchema } from "@/features/fights/schemas/change-fight-status.schema"

interface FightContext {
  params: Promise<{ id: string }>
}

export async function PATCH(request: Request, { params }: FightContext) {
  const id = await readIdParam(params, "id")
  if (!id) return unknownResource("Unknown fight")

  const parsed = await parseRequestBody(request, changeFightStatusSchema)
  if (!parsed.success) return invalidInput(parsed.error)

  return relayApiResult(patchFightStatus(id, parsed.data, await withSession()))
}
