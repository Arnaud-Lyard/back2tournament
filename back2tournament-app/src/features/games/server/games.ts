import "server-only"

import { cache } from "react"
import { createApiClient, getServerApiClient } from "@/libs/api/client"
import { loadApiResult, type Loaded } from "@/libs/api/load"
import { findGame } from "../lib/find-game"
import type { Game } from "../types"

export const loadGames = cache(async () => {
  const client = await getServerApiClient()
  return loadApiResult(client.GET("/api/games/"))
})

export async function loadGame(gameId: string): Promise<Loaded<Game>> {
  const games = await loadGames()
  if (!games.ok) return games

  const game = findGame(games.data, gameId)
  return game ? { ok: true, data: game } : { ok: false, status: 404 }
}

export async function loadPublicGames(): Promise<Game[]> {
  const loaded = await loadApiResult(createApiClient().GET("/api/games/"))
  return loaded.ok ? loaded.data : []
}
