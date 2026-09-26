import { z } from "zod"
import { requiredText } from "@/libs/validation"

/** The game a profile belongs to never changes: only its battletag does. */
export const updatePlayerSchema = z.object({
  battletag: requiredText(),
})

export type UpdatePlayerInput = z.infer<typeof updatePlayerSchema>
