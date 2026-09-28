import type { components, paths } from "@/libs/api/schema"

export type Clan = components["schemas"]["Clan"]

export type ClanSummary = components["schemas"]["ClanSummary"]

export type ClanDetail = components["schemas"]["ClanDetail"]

export type ClanMember = components["schemas"]["ClanMember"]

export type MyClan =
  paths["/api/users/me/clans"]["get"]["responses"][200]["content"]["application/json"][number]

export type Team = components["schemas"]["Team"]

export const CLANS_PER_PAGE = 12
