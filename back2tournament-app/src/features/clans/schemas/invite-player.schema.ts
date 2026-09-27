import { z } from "zod"
import { uuid } from "@/libs/validation"

export const invitePlayerSchema = z.object({
  player: uuid(),
})

export type InvitePlayerInput = z.infer<typeof invitePlayerSchema>
