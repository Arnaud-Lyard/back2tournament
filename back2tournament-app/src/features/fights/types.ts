import type {
  FightSummary,
  GetResultsPendingUserFights200ItemsItem,
  SettledResult,
  SettledResultPage,
} from "@/libs/api/generated/endpoints.schemas"

export type PendingResult = GetResultsPendingUserFights200ItemsItem

export type { FightSummary, SettledResult, SettledResultPage }

export type CreatedFight = FightSummary

export type FightStatusFilter = "reporting" | "pending" | "finished" | "all"

export const ADMIN_FIGHTS_PER_PAGE = 20

export const FIGHTS_PER_PAGE = 10

export type Outcome = SettledResult["outcome"]

export const HISTORY_SIZE = 10
