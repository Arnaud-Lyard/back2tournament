import { z } from "zod"
import { message, requiredName, uuid } from "@/libs/validation"

export const createTournamentSchema = z.object({
  name: requiredName(100),
  game: uuid(),
  teamSize: z.int().min(1).max(64),
  capacity: z
    .int(message("invalidCapacity"))
    .min(2, message("invalidCapacity"))
    .max(128, message("invalidCapacity")),
  /** An ISO 8601 date-time with its offset, in the future. */
  startsAt: z.iso
    .datetime({ offset: true, message: message("invalidDate") })
    .refine(
      (value) => new Date(value).getTime() > Date.now(),
      message("invalidDate")
    ),
})

export type CreateTournamentInput = z.infer<typeof createTournamentSchema>
