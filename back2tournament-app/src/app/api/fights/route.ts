import { postFight } from "@/libs/api/generated/fight"
import { withSession } from "@/libs/api/session"
import {
  invalidInput,
  parseRequestBody,
  relayApiResult,
} from "@/libs/api/route-response"
import { createFightSchema } from "@/features/fights/schemas/create-fight.schema"

export async function POST(request: Request) {
  const parsed = await parseRequestBody(request, createFightSchema)
  if (!parsed.success) return invalidInput(parsed.error)

  return relayApiResult(postFight(parsed.data, await withSession()))
}
