import "server-only"

import { getResultsClan, getResultsPlayer } from "@/libs/api/generated/fight"
import { loadApiResult } from "@/libs/api/load"
import { withSession } from "@/libs/api/session"
import { HISTORY_SIZE } from "../types"

export async function loadPlayerHistory(playerId: string) {
  return loadApiResult(
    getResultsPlayer(playerId, { limit: HISTORY_SIZE }, await withSession())
  )
}

export async function loadClanHistory(clanId: string) {
  return loadApiResult(
    getResultsClan(clanId, { limit: HISTORY_SIZE }, await withSession())
  )
}
