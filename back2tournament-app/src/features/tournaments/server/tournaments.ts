import "server-only"

import { cache } from "react"
import { getServerApiClient } from "@/libs/api/client"
import { loadApiResult } from "@/libs/api/load"
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
  const client = await getServerApiClient()
  return loadApiResult(
    client.GET("/api/tournaments/", {
      params: {
        query: {
          page,
          limit,
          ...(status ? { status } : {}),
          ...(game ? { game } : {}),
        },
      },
    })
  )
}

export const loadTournament = cache(async (tournamentId: string) => {
  const client = await getServerApiClient()
  return loadApiResult(
    client.GET("/api/tournaments/{id}", {
      params: { path: { id: tournamentId } },
    })
  )
})
