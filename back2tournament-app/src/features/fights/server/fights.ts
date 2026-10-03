import "server-only"

import {
  getFight,
  getFightList,
  getResultsPendingUserFights,
} from "@/libs/api/generated/fight"
import { loadApiResult } from "@/libs/api/load"
import { withSession } from "@/libs/api/session"
import {
  ADMIN_FIGHTS_PER_PAGE,
  FIGHTS_PER_PAGE,
  type FightStatusFilter,
} from "../types"

export async function loadChallenges({
  page = 1,
  limit = FIGHTS_PER_PAGE,
}: { page?: number; limit?: number } = {}) {
  return loadApiResult(
    getResultsPendingUserFights({ page, limit }, await withSession())
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
  return loadApiResult(
    getFightList(
      { status, page, limit: ADMIN_FIGHTS_PER_PAGE, q: search || undefined },
      await withSession()
    )
  )
}

export async function loadFight(fightId: string) {
  return loadApiResult(getFight(fightId, await withSession()))
}
