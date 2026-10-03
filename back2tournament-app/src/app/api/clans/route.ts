import { postClan } from "@/libs/api/generated/clan"
import { withSession } from "@/libs/api/session"
import {
  invalidInput,
  parseRequestBody,
  relayApiResult,
} from "@/libs/api/route-response"
import { createClanSchema } from "@/features/clans/schemas/create-clan.schema"

export async function POST(request: Request) {
  const parsed = await parseRequestBody(request, createClanSchema)
  if (!parsed.success) return invalidInput(parsed.error)

  return relayApiResult(postClan(parsed.data, await withSession()))
}
