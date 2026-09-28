import { z } from "zod"
import { uuid } from "@/libs/validation"

export const registerParticipantSchema = z.union([
  z.object({ player: uuid() }),
  z.object({ team: uuid() }),
])

export type RegisterParticipantInput = z.infer<typeof registerParticipantSchema>
