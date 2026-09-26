import "server-only"

import { getServerApiClient } from "@/libs/api/client"
import { loadApiResult } from "@/libs/api/load"
import { FIGHTS_PER_PAGE } from "../types"

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
