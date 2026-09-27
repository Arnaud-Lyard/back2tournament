import type { components, paths } from "@/libs/api/schema"

export type PendingResult =
  paths["/api/results/users/fights"]["get"]["responses"][200]["content"]["application/json"]["items"][number]

export type CreatedFight =
  paths["/api/fights/"]["post"]["responses"][200]["content"]["application/json"]

export type FightSummary = components["schemas"]["FightSummary"]

export const FIGHTS_PER_PAGE = 10

export type SettledResultPage = components["schemas"]["SettledResultPage"]

export type SettledResult = components["schemas"]["SettledResult"]

export type Outcome = SettledResult["outcome"]

/** How many settled fights a profile or a clan page shows. */
export const HISTORY_SIZE = 10
