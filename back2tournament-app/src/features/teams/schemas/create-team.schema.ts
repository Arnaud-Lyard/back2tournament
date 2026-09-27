import { z } from "zod"
import { message, requiredName, uuid } from "@/libs/validation"

/** A lineup of exactly `size` clan members, its leader among them. */
export const createTeamSchema = z
  .object({
    clan: uuid(),
    name: requiredName(),
    size: z.int().min(2).max(64),
    players: z.array(uuid()),
    leader: uuid(),
  })
  .refine((team) => team.players.length === team.size, {
    message: message("lineupSize"),
    path: ["players"],
  })
  .refine((team) => team.players.includes(team.leader), {
    message: message("leaderInLineup"),
    path: ["leader"],
  })

export type CreateTeamInput = z.infer<typeof createTeamSchema>
