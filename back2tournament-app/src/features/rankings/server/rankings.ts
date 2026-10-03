import "server-only"

import {
  getRankingsClan,
  getRankingsClans,
  getRankingsPlayer,
  getRankingsPlayers,
} from "@/libs/api/generated/ranking"
import { loadApiResult } from "@/libs/api/load"
import { withSession } from "@/libs/api/session"
import { RANKING_PAGE_SIZE, type RankingView } from "../types"

export async function loadRanking(
  gameId: string,
  view: RankingView,
  size?: number,
  page = 1
) {
  const session = await withSession()
  const query = { size, page, limit: RANKING_PAGE_SIZE }

  return loadApiResult(
    view === "clans"
      ? getRankingsClans(gameId, query, session)
      : getRankingsPlayers(gameId, query, session)
  )
}

export async function loadPlayerRating(playerId: string) {
  return loadApiResult(getRankingsPlayer(playerId, await withSession()))
}

export async function loadClanRating(clanId: string) {
  return loadApiResult(getRankingsClan(clanId, await withSession()))
}
