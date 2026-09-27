import "server-only"

import { getServerApiClient } from "@/libs/api/client"
import { loadApiResult } from "@/libs/api/load"
import { HISTORY_SIZE } from "../types"

/** The latest settled fights of a player profile: its duels, and its teams'. */
export async function loadPlayerHistory(playerId: string) {
  const client = await getServerApiClient()
  return loadApiResult(
    client.GET("/api/results/players/{id}", {
      params: { path: { id: playerId }, query: { limit: HISTORY_SIZE } },
    })
  )
}

/** The latest settled fights of a clan's teams. */
export async function loadClanHistory(clanId: string) {
  const client = await getServerApiClient()
  return loadApiResult(
    client.GET("/api/results/clans/{id}", {
      params: { path: { id: clanId }, query: { limit: HISTORY_SIZE } },
    })
  )
}
