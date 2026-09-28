import { z } from "zod"
import { uuid } from "@/libs/validation"

export const createFightSchema = z.union([
  z.object({ playerOne: uuid(), playerTwo: uuid() }),
  z.object({ teamOne: uuid(), teamTwo: uuid() }),
])

export type CreateFightInput = z.infer<typeof createFightSchema>
