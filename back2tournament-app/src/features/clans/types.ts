import type {
  Clan,
  ClanDetail,
  ClanMember,
  ClanSummary,
  GetClanMine200Item,
  Team,
} from "@/libs/api/generated/endpoints.schemas"

export type { Clan, ClanDetail, ClanMember, ClanSummary, Team }

export type MyClan = GetClanMine200Item

export const CLANS_PER_PAGE = 12
