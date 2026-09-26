import { getServerApiClient } from "@/libs/api/client"
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

  const client = await getServerApiClient()
  return relayApiResult(
    client.DELETE("/api/tournaments/{id}/participants/{participantid}", {
      params: { path: { id, participantid: participantId } },
    })
  )
}
