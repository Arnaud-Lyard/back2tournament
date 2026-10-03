import { postTournamentParticipant } from "@/libs/api/generated/tournament"
import { withSession } from "@/libs/api/session"
import { readIdParam, unknownResource } from "@/libs/api/route-params"
import {
  invalidInput,
  parseRequestBody,
  relayApiResult,
} from "@/libs/api/route-response"
import { registerParticipantSchema } from "@/features/tournaments/schemas/register-participant.schema"

interface TournamentContext {
  params: Promise<{ id: string }>
}

export async function POST(request: Request, { params }: TournamentContext) {
  const id = await readIdParam(params, "id")
  if (!id) return unknownResource("Unknown tournament")

  const parsed = await parseRequestBody(request, registerParticipantSchema)
  if (!parsed.success) return invalidInput(parsed.error)

  return relayApiResult(
    postTournamentParticipant(id, parsed.data, await withSession())
  )
}
