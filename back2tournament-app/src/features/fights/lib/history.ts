import type { Outcome, SettledResult } from "../types"

export const OUTCOME_VARIANT = {
  win: "default",
  loss: "destructive",
  draw: "secondary",
} as const satisfies Record<Outcome, string>

export function opponentHref(result: SettledResult): string | null {
  const opponent = result.opponent
  if (opponent?.type !== "player" || !opponent.reference?.value) return null

  return `/games/${encodeURIComponent(result.game.value ?? "")}/players/${encodeURIComponent(opponent.reference.value)}`
}
