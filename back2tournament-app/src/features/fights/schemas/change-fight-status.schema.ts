import { z } from "zod"
import { score } from "./declare-results.schema"

export const changeFightStatusSchema = z.discriminatedUnion("status", [
  z.object({
    status: z.literal("finished"),
    scores: z.record(z.string().min(1), score()),
  }),
  z.object({ status: z.literal("pending") }),
])

export type ChangeFightStatusInput = z.infer<typeof changeFightStatusSchema>

export const arbitrationScoresSchema = z.object({
  scoreOne: score(),
  scoreTwo: score(),
})
