import { postTournament } from "@/libs/api/generated/tournament"
import { withSession } from "@/libs/api/session"
import {
  invalidInput,
  parseRequestBody,
  relayApiResult,
} from "@/libs/api/route-response"
import { createTournamentSchema } from "@/features/tournaments/schemas/create-tournament.schema"

export async function POST(request: Request) {
  const parsed = await parseRequestBody(request, createTournamentSchema)
  if (!parsed.success) return invalidInput(parsed.error)

  return relayApiResult(postTournament(parsed.data, await withSession()))
}
