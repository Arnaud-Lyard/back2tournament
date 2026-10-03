import type {
  RankedSubject,
  SubjectRating,
} from "@/libs/api/generated/endpoints.schemas"

export type { RankedSubject, SubjectRating }

export type RankingView = "players" | "clans"

export const RANKING_PAGE_SIZE = 20
