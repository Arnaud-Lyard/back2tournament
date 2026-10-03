import "server-only"

import { cache } from "react"
import { getGameList } from "@/libs/api/generated/game"
import { loadApiResult, type Loaded } from "@/libs/api/load"
import { withSession } from "@/libs/api/session"
import { findGame } from "../lib/find-game"
import type { Game } from "../types"

export const loadGames = cache(async () => {
  return loadApiResult(getGameList(await withSession()))
})

export async function loadGame(gameId: string): Promise<Loaded<Game>> {
  const games = await loadGames()
  if (!games.ok) return games

  const game = findGame(games.data, gameId)
  return game ? { ok: true, data: game } : { ok: false, status: 404 }
}

export async function loadPublicGames(): Promise<Game[]> {
  const loaded = await loadApiResult(getGameList())
  return loaded.ok ? loaded.data : []
}
