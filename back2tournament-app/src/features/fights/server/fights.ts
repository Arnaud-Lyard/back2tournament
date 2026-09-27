import "server-only"

import { getServerApiClient } from "@/libs/api/client"
import { loadApiResult } from "@/libs/api/load"
import {
  ADMIN_FIGHTS_PER_PAGE,
  FIGHTS_PER_PAGE,
  type FightStatusFilter,
} from "../types"

export async function loadChallenges({
  page = 1,
  limit = FIGHTS_PER_PAGE,
}: { page?: number; limit?: number } = {}) {
  const client = await getServerApiClient()
  return loadApiResult(
    client.GET("/api/results/users/fights", {
      params: { query: { page, limit } },
    })
  )
}

/** Admin only: one page of every fight, by status and by the name of a side. */
export async function loadFights({
  status,
  search = "",
  page = 1,
}: {
  status: FightStatusFilter
  search?: string
  page?: number
}) {
  const client = await getServerApiClient()
  return loadApiResult(
    client.GET("/api/fights/", {
      params: {
        query: {
          status,
          page,
          limit: ADMIN_FIGHTS_PER_PAGE,
          ...(search ? { q: search } : {}),
        },
      },
    })
  )
}

/** One fight and both its sides. */
export async function loadFight(fightId: string) {
  const client = await getServerApiClient()
  return loadApiResult(
    client.GET("/api/fights/{id}", { params: { path: { id: fightId } } })
  )
}
