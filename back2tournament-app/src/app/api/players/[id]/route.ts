import { NextResponse } from "next/server"
import { deletePlayer, patchPlayer } from "@/libs/api/generated/player"
import { withSession } from "@/libs/api/session"
import {
  invalidInput,
  parseRequestBody,
  relayApiResult,
} from "@/libs/api/route-response"
import { updatePlayerSchema } from "@/features/players/schemas/update-player.schema"
import { uuid } from "@/libs/validation"

interface PlayerContext {
  params: Promise<{ id: string }>
}

export async function PATCH(request: Request, { params }: PlayerContext) {
  const id = await readPlayerId(params)
  if (!id) return unknownPlayer()

  const parsed = await parseRequestBody(request, updatePlayerSchema)
  if (!parsed.success) return invalidInput(parsed.error)

  return relayApiResult(patchPlayer(id, parsed.data, await withSession()))
}

export async function DELETE(_request: Request, { params }: PlayerContext) {
  const id = await readPlayerId(params)
  if (!id) return unknownPlayer()

  return relayApiResult(deletePlayer(id, await withSession()))
}

async function readPlayerId(
  params: PlayerContext["params"]
): Promise<string | undefined> {
  const parsed = uuid().safeParse((await params).id)
  return parsed.success ? parsed.data : undefined
}

function unknownPlayer(): NextResponse {
  return NextResponse.json(
    { message: "Unknown player profile" },
    { status: 404 }
  )
}
