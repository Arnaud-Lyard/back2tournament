import type { components } from "@/libs/api/schema"

export type RankedSubject = components["schemas"]["RankedSubject"]

/** The rating of one player profile or clan, and its rank in its game. */
export type SubjectRating = components["schemas"]["SubjectRating"]

/** A game has two rankings: its player profiles, on their duels, and its clans. */
export type RankingView = "players" | "clans"

export const RANKING_PAGE_SIZE = 20
