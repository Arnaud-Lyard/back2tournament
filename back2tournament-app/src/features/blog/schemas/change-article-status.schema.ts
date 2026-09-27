import { z } from "zod"

export const changeArticleStatusSchema = z.object({
  status: z.enum(["draft", "published"]),
})

export type ChangeArticleStatusInput = z.infer<typeof changeArticleStatusSchema>
