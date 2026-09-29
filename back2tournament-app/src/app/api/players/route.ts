import { getServerApiClient } from "@/libs/api/client"
import {
  invalidInput,
  parseRequestBody,
  relayApiResult,
} from "@/libs/api/route-response"
import { createPlayerSchema } from "@/features/players/schemas/create-player.schema"

export async function POST(request: Request) {
  const parsed = await parseRequestBody(request, createPlayerSchema)
  if (!parsed.success) return invalidInput(parsed.error)

  const client = await getServerApiClient()
  return relayApiResult(
    client.POST("/api/user/players/", { body: parsed.data })
  )
}
