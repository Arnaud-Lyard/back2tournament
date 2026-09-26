import { getServerApiClient } from "@/libs/api/client"
import {
  invalidInput,
  parseRequestBody,
  relayApiResult,
} from "@/libs/api/route-response"
import { createFightSchema } from "@/features/fights/schemas/create-fight.schema"

export async function POST(request: Request) {
  const parsed = await parseRequestBody(request, createFightSchema)
  if (!parsed.success) return invalidInput(parsed.error)

  const client = await getServerApiClient()
  return relayApiResult(client.POST("/api/fights/", { body: parsed.data }))
}
