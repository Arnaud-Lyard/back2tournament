import type { components } from "@/libs/api/schema"

export type RankedSubject = components["schemas"]["RankedSubject"]

/** The ratings of one player profile or clan, one per format of its game. */
export type SubjectRating = components["schemas"]["SubjectRating"]

/** A game has two rankings per format: its player profiles, and its clans. */
export type RankingView = "players" | "clans"

export const RANKING_PAGE_SIZE = 20
