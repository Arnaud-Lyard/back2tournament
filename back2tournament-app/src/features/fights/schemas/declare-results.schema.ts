import { z } from "zod"
import { message } from "@/libs/validation"

export function score() {
  return z
    .number(message("invalidScore"))
    .int(message("invalidScore"))
    .min(0, message("invalidScore"))
}

export const declareResultsSchema = z.object({
  score: score(),
  opponentScore: score(),
})

export type DeclareResultsInput = z.infer<typeof declareResultsSchema>
