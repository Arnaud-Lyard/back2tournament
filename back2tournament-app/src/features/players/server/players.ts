import "server-only"

import { getPlayer, getPlayerList } from "@/libs/api/generated/player"
import { loadApiResult } from "@/libs/api/load"
import { withSession } from "@/libs/api/session"
import { PLAYERS_PER_PAGE } from "../types"

interface PlayersQuery {
  page?: number
  search?: string
  limit?: number
}

export async function loadGamePlayers(
  gameId: string,
  { page = 1, search = "", limit = PLAYERS_PER_PAGE }: PlayersQuery = {}
) {
  return loadApiResult(
    getPlayerList(
      gameId,
      { page, limit, q: search || undefined },
      await withSession()
    )
  )
}

export async function loadPlayer(playerId: string) {
  return loadApiResult(getPlayer(playerId, await withSession()))
}
