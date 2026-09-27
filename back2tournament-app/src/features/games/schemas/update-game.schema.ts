import { z } from "zod"
import { teamSizes } from "./create-game.schema"

export const updateGameSchema = z.object({
  teamSizes: teamSizes(),
})

export type UpdateGameInput = z.infer<typeof updateGameSchema>
