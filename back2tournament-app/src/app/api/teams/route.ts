import { getServerApiClient } from "@/libs/api/client"
import {
  invalidInput,
  parseRequestBody,
  relayApiResult,
} from "@/libs/api/route-response"
import { createTeamSchema } from "@/features/teams/schemas/create-team.schema"

export async function POST(request: Request) {
  const parsed = await parseRequestBody(request, createTeamSchema)
  if (!parsed.success) return invalidInput(parsed.error)

  const client = await getServerApiClient()
  return relayApiResult(client.POST("/api/teams/", { body: parsed.data }))
}
