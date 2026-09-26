import { getServerApiClient } from "@/libs/api/client"
import {
  invalidInput,
  parseRequestBody,
  relayApiResult,
} from "@/libs/api/route-response"
import { createGameSchema } from "@/features/games/schemas/create-game.schema"

export async function POST(request: Request) {
  const parsed = await parseRequestBody(request, createGameSchema)
  if (!parsed.success) return invalidInput(parsed.error)

  const client = await getServerApiClient()
  return relayApiResult(client.POST("/api/games/", { body: parsed.data }))
}
