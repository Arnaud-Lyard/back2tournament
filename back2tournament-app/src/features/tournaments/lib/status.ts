import { TOURNAMENT_STATUSES, type TournamentStatus } from "../types"

export const STATUS_VARIANT = {
  upcoming: "default",
  ongoing: "secondary",
  finished: "outline",
  cancelled: "outline",
} as const satisfies Record<TournamentStatus, string>

export function isValidStatus(value: string): value is TournamentStatus {
  return (TOURNAMENT_STATUSES as readonly string[]).includes(value)
}
