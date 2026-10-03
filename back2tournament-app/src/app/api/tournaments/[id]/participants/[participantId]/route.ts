import { deleteTournamentParticipant } from "@/libs/api/generated/tournament"
import { withSession } from "@/libs/api/session"
import { readIdParam, unknownResource } from "@/libs/api/route-params"
import { relayApiResult } from "@/libs/api/route-response"

interface ParticipantContext {
  params: Promise<{ id: string; participantId: string }>
}

export async function DELETE(
  _request: Request,
  { params }: ParticipantContext
) {
  const id = await readIdParam(params, "id")
  const participantId = await readIdParam(params, "participantId")
  if (!id || !participantId) return unknownResource("Unknown registration")

  return relayApiResult(
    deleteTournamentParticipant(id, participantId, await withSession())
  )
}
