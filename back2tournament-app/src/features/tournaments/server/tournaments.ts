import "server-only"

import { cache } from "react"
import {
  getTournament,
  getTournamentList,
} from "@/libs/api/generated/tournament"
import { loadApiResult } from "@/libs/api/load"
import { withSession } from "@/libs/api/session"
import { TOURNAMENTS_PER_PAGE, type TournamentStatus } from "../types"

interface TournamentsQuery {
  page?: number
  status?: TournamentStatus
  game?: string
  limit?: number
}

export async function loadTournaments({
  page = 1,
  status,
  game,
  limit = TOURNAMENTS_PER_PAGE,
}: TournamentsQuery = {}) {
  return loadApiResult(
    getTournamentList(
      { page, limit, status, game: game || undefined },
      await withSession()
    )
  )
}

export const loadTournament = cache(async (tournamentId: string) => {
  return loadApiResult(getTournament(tournamentId, await withSession()))
})
