import "server-only"

import { cache } from "react"
import { getClan, getClanList, getClanMine } from "@/libs/api/generated/clan"
import { loadApiResult, type Loaded } from "@/libs/api/load"
import { withSession } from "@/libs/api/session"
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
  return loadApiResult(
    getClanList(
      gameId,
      { page, limit, q: search || undefined },
      await withSession()
    )
  )
}

export const loadClan = cache(async (clanId: string) => {
  return loadApiResult(getClan(clanId, await withSession()))
})

export const loadMyClans = cache(async (): Promise<Loaded<MyClan[]>> => {
  if (!(await getCurrentUser())) return { ok: true, data: [] }

  return loadApiResult(getClanMine(await withSession()))
})
