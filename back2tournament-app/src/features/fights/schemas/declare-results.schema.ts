import { z } from "zod"
import { message } from "@/libs/validation"

function score() {
  return z
    .number(message("invalidScore"))
    .int(message("invalidScore"))
    .min(0, message("invalidScore"))
}

/** The scores of a fight, seen from the declaring side. */
export const declareResultsSchema = z.object({
  score: score(),
  opponentScore: score(),
})

export type DeclareResultsInput = z.infer<typeof declareResultsSchema>
