import { z } from "zod"
import { requiredText, uuid } from "@/libs/validation"

export const createPlayerSchema = z.object({
  game: uuid(),
  battletag: requiredText(),
})

export type CreatePlayerInput = z.infer<typeof createPlayerSchema>
