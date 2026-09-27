import { z } from "zod"
import { message, requiredName, uuid } from "@/libs/validation"

export const createClanSchema = z.object({
  game: uuid(),
  name: requiredName(),
  tag: z
    .string()
    .trim()
    .toUpperCase()
    .regex(/^[A-Z0-9]{2,5}$/, message("invalidClanTag")),
})

export type CreateClanInput = z.infer<typeof createClanSchema>
