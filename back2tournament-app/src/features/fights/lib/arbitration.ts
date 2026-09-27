import type { FightStatusFilter } from "../types"

const FILTERS: readonly FightStatusFilter[] = [
  "reporting",
  "pending",
  "finished",
  "all",
]

/**
 * The fights the backoffice lists: those waiting for a confirmation unless
 * another status is asked for, since the disputes are among them.
 */
export function readFightStatusFilter(
  value: string | string[] | undefined
): FightStatusFilter {
  const raw = Array.isArray(value) ? value[0] : value
  return FILTERS.find((filter) => filter === raw) ?? "reporting"
}

export const FIGHT_STATUS_FILTERS = FILTERS
