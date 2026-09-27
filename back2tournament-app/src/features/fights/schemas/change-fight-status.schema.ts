import { z } from "zod"
import { score } from "./declare-results.schema"

/**
 * What an administrator does with a fight in dispute: settle it on the score
 * of each side, keyed by competitor id, or set its declaration aside.
 */
export const changeFightStatusSchema = z.discriminatedUnion("status", [
  z.object({
    status: z.literal("finished"),
    scores: z.record(z.string().min(1), score()),
  }),
  z.object({ status: z.literal("pending") }),
])

export type ChangeFightStatusInput = z.infer<typeof changeFightStatusSchema>

/** The two scores the arbitration form asks for, in the order the fight holds its sides. */
export const arbitrationScoresSchema = z.object({
  scoreOne: score(),
  scoreTwo: score(),
})
