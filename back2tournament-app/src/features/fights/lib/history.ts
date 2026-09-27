import type { Outcome, SettledResult } from "../types"

/** How each outcome reads as a badge: a win stands out, a loss warns. */
export const OUTCOME_VARIANT = {
  win: "default",
  loss: "destructive",
  draw: "secondary",
} as const satisfies Record<Outcome, string>

/** The profile page of the other side, for a duel; a team has none. */
export function opponentHref(result: SettledResult): string | null {
  const opponent = result.opponent
  if (opponent?.type !== "player" || !opponent.reference?.value) return null

  return `/games/${encodeURIComponent(result.game.value ?? "")}/players/${encodeURIComponent(opponent.reference.value)}`
}
