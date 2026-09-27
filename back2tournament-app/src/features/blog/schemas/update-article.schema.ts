import type { z } from "zod"
import { articleFields, refineEnglishVersion } from "./create-article.schema"

/**
 * The same rules as a new article, every field optional: a field left out
 * keeps what the article has. Both English fields sent empty remove the
 * English version.
 */
export const updateArticleSchema = articleFields
  .partial()
  .superRefine(refineEnglishVersion)

export type UpdateArticleInput = z.infer<typeof updateArticleSchema>
