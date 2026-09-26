import { z } from "zod"
import { message, uuid } from "@/libs/validation"

const MAX_COMMENT_LENGTH = 2000

export const createCommentSchema = z.object({
  articleId: uuid(),
  message: z
    .string()
    .trim()
    .min(1, message("required"))
    .max(MAX_COMMENT_LENGTH, message("messageTooLong")),
})

export type CreateCommentInput = z.infer<typeof createCommentSchema>
