import type { components } from "@/libs/api/schema"

export type TournamentSummary = components["schemas"]["TournamentSummary"]

export type TournamentDetail = components["schemas"]["TournamentDetail"]

export type TournamentParticipant =
  components["schemas"]["TournamentParticipant"]

export type TournamentMatchup = components["schemas"]["TournamentMatchup"]

export type TournamentStatus = NonNullable<TournamentSummary["status"]>

export const TOURNAMENT_STATUSES: readonly TournamentStatus[] = [
  "upcoming",
  "ongoing",
  "finished",
  "cancelled",
]

export const TOURNAMENTS_PER_PAGE = 12
