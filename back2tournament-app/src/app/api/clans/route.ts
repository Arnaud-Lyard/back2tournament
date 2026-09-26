import { getServerApiClient } from "@/libs/api/client"
import {
  invalidInput,
  parseRequestBody,
  relayApiResult,
} from "@/libs/api/route-response"
import { createClanSchema } from "@/features/clans/schemas/create-clan.schema"

export async function POST(request: Request) {
  const parsed = await parseRequestBody(request, createClanSchema)
  if (!parsed.success) return invalidInput(parsed.error)

  const client = await getServerApiClient()
  return relayApiResult(client.POST("/api/clans/", { body: parsed.data }))
}
