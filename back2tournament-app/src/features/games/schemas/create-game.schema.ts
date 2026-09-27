import { z } from "zod"
import { message, requiredText } from "@/libs/validation"

/** Players per side of each format the game is played in: [1] for 1v1 only. */
export function teamSizes() {
  return z
    .array(z.int().min(1).max(64), message("invalidTeamSizes"))
    .min(1, message("invalidTeamSizes"))
}

export const createGameSchema = z.object({
  title: requiredText(),
  teamSizes: teamSizes(),
})

export type CreateGameInput = z.infer<typeof createGameSchema>
