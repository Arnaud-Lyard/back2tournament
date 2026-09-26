import "server-only"

import { getServerApiClient } from "@/libs/api/client"
import { loadApiResult } from "@/libs/api/load"
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
  const client = await getServerApiClient()
  return loadApiResult(
    client.GET("/api/players/{id}/games", {
      params: {
        path: { id: gameId },
        query: { page, limit, ...(search ? { q: search } : {}) },
      },
    })
  )
}

export async function loadPlayer(playerId: string) {
  const client = await getServerApiClient()
  return loadApiResult(
    client.GET("/api/players/{id}", { params: { path: { id: playerId } } })
  )
}
