import { z } from "zod"
import { requiredText, slug } from "@/libs/validation"

export const createCategorySchema = z.object({
  name: requiredText(),
  slug: slug(),
})

export type CreateCategoryInput = z.infer<typeof createCategorySchema>
