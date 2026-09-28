import type { FightStatusFilter } from "../types"

const FILTERS: readonly FightStatusFilter[] = [
  "reporting",
  "pending",
  "finished",
  "all",
]

export function readFightStatusFilter(
  value: string | string[] | undefined
): FightStatusFilter {
  const raw = Array.isArray(value) ? value[0] : value
  return FILTERS.find((filter) => filter === raw) ?? "reporting"
}

export const FIGHT_STATUS_FILTERS = FILTERS
