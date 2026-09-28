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
    client.GET("/api/user/results/fights", {
      params: { query: { page, limit } },
    })
  )
}

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
    client.GET("/api/admin/fights/", {
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

export async function loadFight(fightId: string) {
  const client = await getServerApiClient()
  return loadApiResult(
    client.GET("/api/user/fights/{id}", { params: { path: { id: fightId } } })
  )
}
