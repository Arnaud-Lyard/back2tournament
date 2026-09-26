import { z } from "zod"
import { message, requiredText, slug } from "@/libs/validation"

export const createArticleSchema = z.object({
  title: requiredText(),
  categorySlug: slug(),
  body: z.string().trim().min(1, message("required")),
})

export type CreateArticleInput = z.infer<typeof createArticleSchema>
