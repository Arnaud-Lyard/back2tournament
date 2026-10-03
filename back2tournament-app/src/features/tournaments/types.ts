import {
  TournamentSummaryStatus,
  type TournamentDetail,
  type TournamentMatchup,
  type TournamentParticipant,
} from "@/libs/api/generated/endpoints.schemas"

export type { TournamentDetail, TournamentMatchup, TournamentParticipant }

export type TournamentStatus = TournamentSummaryStatus

export const TOURNAMENT_STATUSES: readonly TournamentStatus[] = Object.values(
  TournamentSummaryStatus
)

export const TOURNAMENTS_PER_PAGE = 12
