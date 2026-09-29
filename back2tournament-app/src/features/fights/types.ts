import type { components, paths } from "@/libs/api/schema"

export type PendingResult =
  paths["/api/user/results/fights"]["get"]["responses"][200]["content"]["application/json"]["items"][number]

export type CreatedFight =
  paths["/api/user/fights/"]["post"]["responses"][200]["content"]["application/json"]

export type FightSummary = components["schemas"]["FightSummary"]

export type FightStatusFilter = "reporting" | "pending" | "finished" | "all"

export const ADMIN_FIGHTS_PER_PAGE = 20

export const FIGHTS_PER_PAGE = 10

export type SettledResultPage = components["schemas"]["SettledResultPage"]

export type SettledResult = components["schemas"]["SettledResult"]

export type Outcome = SettledResult["outcome"]

export const HISTORY_SIZE = 10
