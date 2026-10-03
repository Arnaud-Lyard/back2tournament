import { postTeam } from "@/libs/api/generated/team"
import { withSession } from "@/libs/api/session"
import {
  invalidInput,
  parseRequestBody,
  relayApiResult,
} from "@/libs/api/route-response"
import { createTeamSchema } from "@/features/teams/schemas/create-team.schema"

export async function POST(request: Request) {
  const parsed = await parseRequestBody(request, createTeamSchema)
  if (!parsed.success) return invalidInput(parsed.error)

  return relayApiResult(postTeam(parsed.data, await withSession()))
}
