import { postGame } from "@/libs/api/generated/game"
import { withSession } from "@/libs/api/session"
import {
  invalidInput,
  parseRequestBody,
  relayApiResult,
} from "@/libs/api/route-response"
import { createGameSchema } from "@/features/games/schemas/create-game.schema"

export async function POST(request: Request) {
  const parsed = await parseRequestBody(request, createGameSchema)
  if (!parsed.success) return invalidInput(parsed.error)

  return relayApiResult(postGame(parsed.data, await withSession()))
}
