import { z } from "zod"
import { uuid } from "@/libs/validation"

export const createFightSchema = z.object({
  playerOne: uuid(),
  playerTwo: uuid(),
})

export type CreateFightInput = z.infer<typeof createFightSchema>
