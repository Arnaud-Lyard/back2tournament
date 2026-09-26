import type { PendingResult } from "../types"

/**
 * What the caller can do with a fight still waiting on its result:
 * - `declare`: nobody declared yet, either side may;
 * - `awaiting`: the caller's side declared, the other side has to confirm
 *   (the caller may still correct the declaration);
 * - `confirm`: the other side declared, the caller confirms it — or does not,
 *   and settles the disagreement with an admin.
 */
export type ChallengeStage = "declare" | "awaiting" | "confirm"

export function challengeStage(result: PendingResult): ChallengeStage {
  if (result.status !== "reporting") return "declare"

  const mine = result.side?.competitor?.value ?? result.competitor?.value
  return result.declaredBy?.value === mine ? "awaiting" : "confirm"
}

/** "1v1", "5v5": players per side, as players name a format. */
export function formatLabel(teamSize: number | undefined): string {
  const size = teamSize && teamSize > 0 ? teamSize : 1
  return `${size}v${size}`
}
