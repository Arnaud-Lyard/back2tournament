import { z } from "zod"
import { message } from "@/libs/validation"

export const loginSchema = z.object({
  username: z.string().trim().min(1, message("required")),
  password: z.string().min(1, message("required")),
})

export type LoginInput = z.infer<typeof loginSchema>
