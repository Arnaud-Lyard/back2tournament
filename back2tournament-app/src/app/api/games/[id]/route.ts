import { getServerApiClient } from "@/libs/api/client"
import { readIdParam, unknownResource } from "@/libs/api/route-params"
import {
  invalidInput,
  parseRequestBody,
  relayApiResult,
} from "@/libs/api/route-response"
import { updateGameSchema } from "@/features/games/schemas/update-game.schema"

interface GameContext {
  params: Promise<{ id: string }>
}

export async function PATCH(request: Request, { params }: GameContext) {
  const id = await readIdParam(params, "id")
  if (!id) return unknownResource("Unknown game")

  const parsed = await parseRequestBody(request, updateGameSchema)
  if (!parsed.success) return invalidInput(parsed.error)

  const client = await getServerApiClient()
  return relayApiResult(
    client.PATCH("/api/games/{id}", {
      params: { path: { id } },
      body: parsed.data,
    })
  )
}
