import { z } from "zod"
import { uuid } from "@/libs/validation"

/** 1v1 between two player profiles, or NvN between two teams of one format. */
export const createFightSchema = z.union([
  z.object({ playerOne: uuid(), playerTwo: uuid() }),
  z.object({ teamOne: uuid(), teamTwo: uuid() }),
])

export type CreateFightInput = z.infer<typeof createFightSchema>
