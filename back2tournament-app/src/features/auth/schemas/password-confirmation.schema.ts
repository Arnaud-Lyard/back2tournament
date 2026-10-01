import { z } from "zod"

export const passwordConfirmationSchema = z.object({
  password: z.string().min(1),
})
