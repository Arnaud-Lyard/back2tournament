import { z } from "zod"
import { requiredText } from "@/libs/validation"

export const updatePlayerSchema = z.object({
  battletag: requiredText(),
})

export type UpdatePlayerInput = z.infer<typeof updatePlayerSchema>
