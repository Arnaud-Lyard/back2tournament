import { z } from "zod"
import { requiredText } from "@/libs/validation"

export const createGameSchema = z.object({
  title: requiredText(),
})

export type CreateGameInput = z.infer<typeof createGameSchema>
