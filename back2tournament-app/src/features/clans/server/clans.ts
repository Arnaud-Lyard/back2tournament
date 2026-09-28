import "server-only"

import { cache } from "react"
import { getServerApiClient } from "@/libs/api/client"
import { loadApiResult, type Loaded } from "@/libs/api/load"
import { getCurrentUser } from "@/features/auth/server/get-current-user"
import { CLANS_PER_PAGE, type MyClan } from "../types"

interface ClansQuery {
  page?: number
  search?: string
  limit?: number
}

export async function loadGameClans(
  gameId: string,
  { page = 1, search = "", limit = CLANS_PER_PAGE }: ClansQuery = {}
) {
  const client = await getServerApiClient()
  return loadApiResult(
    client.GET("/api/games/{id}/clans", {
      params: {
        path: { id: gameId },
        query: { page, limit, ...(search ? { q: search } : {}) },
      },
    })
  )
}

export const loadClan = cache(async (clanId: string) => {
  const client = await getServerApiClient()
  return loadApiResult(
    client.GET("/api/clans/{id}", { params: { path: { id: clanId } } })
  )
})

export const loadMyClans = cache(async (): Promise<Loaded<MyClan[]>> => {
  if (!(await getCurrentUser())) return { ok: true, data: [] }

  const client = await getServerApiClient()
  return loadApiResult(client.GET("/api/users/me/clans"))
})
