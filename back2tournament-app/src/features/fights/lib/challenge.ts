import type { PendingResult } from "../types"

export type ChallengeStage = "declare" | "awaiting" | "confirm"

export function challengeStage(result: PendingResult): ChallengeStage {
  if (result.status !== "reporting") return "declare"

  const mine = result.side?.competitor?.value ?? result.competitor?.value
  return result.declaredBy?.value === mine ? "awaiting" : "confirm"
}

export function formatLabel(teamSize: number | undefined): string {
  const size = teamSize && teamSize > 0 ? teamSize : 1
  return `${size}v${size}`
}
