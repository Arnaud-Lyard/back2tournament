import "server-only"

import { getServerApiClient } from "@/libs/api/client"
import { loadApiResult } from "@/libs/api/load"
import { RANKING_PAGE_SIZE, type RankingView } from "../types"

/** One page of a game's ranking of player profiles, or of clans. */
export async function loadRanking(gameId: string, view: RankingView, page = 1) {
  const client = await getServerApiClient()
  const params = {
    path: { gameId },
    query: { page, limit: RANKING_PAGE_SIZE },
  }

  return loadApiResult(
    view === "clans"
      ? client.GET("/api/rankings/games/{gameId}/clans", { params })
      : client.GET("/api/rankings/games/{gameId}/players", { params })
  )
}

/** The rating of a player profile, and its rank in its game. */
export async function loadPlayerRating(playerId: string) {
  const client = await getServerApiClient()
  return loadApiResult(
    client.GET("/api/rankings/players/{id}", {
      params: { path: { id: playerId } },
    })
  )
}

/** The rating of a clan, and its rank in its game. */
export async function loadClanRating(clanId: string) {
  const client = await getServerApiClient()
  return loadApiResult(
    client.GET("/api/rankings/clans/{id}", {
      params: { path: { id: clanId } },
    })
  )
}
