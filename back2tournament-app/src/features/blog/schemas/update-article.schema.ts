import type { z } from "zod"
import { createArticleSchema } from "./create-article.schema"

/**
 * The same rules as a new article, every field optional: a field left out
 * keeps what the article has.
 */
export const updateArticleSchema = createArticleSchema.partial()

export type UpdateArticleInput = z.infer<typeof updateArticleSchema>
