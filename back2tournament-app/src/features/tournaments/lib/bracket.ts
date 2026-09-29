import type { TournamentMatchup } from "../types"

export type RoundName =
  | { key: "final" }
  | { key: "semiFinals" }
  | { key: "quarterFinals" }
  | { key: "roundOf"; count: number }

export function roundName(round: number, rounds: number): RoundName {
  const left = rounds - round
  if (left === 0) return { key: "final" }
  if (left === 1) return { key: "semiFinals" }
  if (left === 2) return { key: "quarterFinals" }
  return { key: "roundOf", count: 2 ** (left + 1) }
}

export function bracketRounds(
  matchups: readonly TournamentMatchup[]
): TournamentMatchup[][] {
  const rounds: TournamentMatchup[][] = []
  for (const matchup of matchups) {
    const index = (matchup.round ?? 1) - 1
    ;(rounds[index] ??= []).push(matchup)
  }

  return rounds.map((round) =>
    [...round].sort((one, two) => (one.position ?? 0) - (two.position ?? 0))
  )
}
