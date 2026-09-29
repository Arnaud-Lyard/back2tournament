import "server-only"

import { getServerApiClient } from "@/libs/api/client"
import { loadApiResult } from "@/libs/api/load"
import { RANKING_PAGE_SIZE, type RankingView } from "../types"

export async function loadRanking(
  gameId: string,
  view: RankingView,
  size?: number,
  page = 1
) {
  const client = await getServerApiClient()
  const params = {
    path: { gameId },
    query: { size, page, limit: RANKING_PAGE_SIZE },
  }

  return loadApiResult(
    view === "clans"
      ? client.GET("/api/rankings/games/{gameId}/clans", { params })
      : client.GET("/api/rankings/games/{gameId}/players", { params })
  )
}

export async function loadPlayerRating(playerId: string) {
  const client = await getServerApiClient()
  return loadApiResult(
    client.GET("/api/rankings/players/{id}", {
      params: { path: { id: playerId } },
    })
  )
}

export async function loadClanRating(clanId: string) {
  const client = await getServerApiClient()
  return loadApiResult(
    client.GET("/api/rankings/clans/{id}", {
      params: { path: { id: clanId } },
    })
  )
}
