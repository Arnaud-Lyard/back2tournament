import { getServerApiClient } from "@/libs/api/client"
import {
  invalidInput,
  parseRequestBody,
  relayApiResult,
} from "@/libs/api/route-response"
import { createTournamentSchema } from "@/features/tournaments/schemas/create-tournament.schema"

export async function POST(request: Request) {
  const parsed = await parseRequestBody(request, createTournamentSchema)
  if (!parsed.success) return invalidInput(parsed.error)

  const client = await getServerApiClient()
  return relayApiResult(client.POST("/api/tournaments/", { body: parsed.data }))
}
