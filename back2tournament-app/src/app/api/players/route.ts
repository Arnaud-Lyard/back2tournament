import { postPlayer } from "@/libs/api/generated/player"
import { withSession } from "@/libs/api/session"
import {
  invalidInput,
  parseRequestBody,
  relayApiResult,
} from "@/libs/api/route-response"
import { createPlayerSchema } from "@/features/players/schemas/create-player.schema"

export async function POST(request: Request) {
  const parsed = await parseRequestBody(request, createPlayerSchema)
  if (!parsed.success) return invalidInput(parsed.error)

  return relayApiResult(postPlayer(parsed.data, await withSession()))
}
