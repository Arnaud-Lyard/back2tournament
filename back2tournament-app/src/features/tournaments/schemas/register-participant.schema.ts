import { z } from "zod"
import { uuid } from "@/libs/validation"

/** A player profile for a 1v1 tournament, or a team for an NvN one. */
export const registerParticipantSchema = z.union([
  z.object({ player: uuid() }),
  z.object({ team: uuid() }),
])

export type RegisterParticipantInput = z.infer<typeof registerParticipantSchema>
