import type { components } from "@/libs/api/schema"

export type RankedSubject = components["schemas"]["RankedSubject"]

export type SubjectRating = components["schemas"]["SubjectRating"]

export type RankingView = "players" | "clans"

export const RANKING_PAGE_SIZE = 20
