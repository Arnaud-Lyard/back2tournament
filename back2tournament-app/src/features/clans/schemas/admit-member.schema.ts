import { z } from "zod"
import { uuid } from "@/libs/validation"

export const admitMemberSchema = z.object({
  player: uuid(),
})
