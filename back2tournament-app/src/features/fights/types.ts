import type { paths } from "@/libs/api/schema"

export type PendingResult =
  paths["/api/results/users/fights"]["get"]["responses"][200]["content"]["application/json"]["items"][number]

export type CreatedFight =
  paths["/api/fights/"]["post"]["responses"][200]["content"]["application/json"]

export const FIGHTS_PER_PAGE = 10
