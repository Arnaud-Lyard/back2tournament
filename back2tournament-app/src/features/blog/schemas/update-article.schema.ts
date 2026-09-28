import type { z } from "zod"
import { articleFields, refineEnglishVersion } from "./create-article.schema"

export const updateArticleSchema = articleFields
  .partial()
  .superRefine(refineEnglishVersion)

export type UpdateArticleInput = z.infer<typeof updateArticleSchema>
