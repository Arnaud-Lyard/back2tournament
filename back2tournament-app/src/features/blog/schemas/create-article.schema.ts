import { z } from "zod"
import { message, requiredText, slug } from "@/libs/validation"

export const articleFields = z.object({
  title: requiredText(),
  categorySlug: slug(),
  body: z.string().trim().min(1, message("required")),
  titleEn: z.string().trim().max(255, message("tooLong")).optional(),
  bodyEn: z.string().trim().optional(),
})

export function refineEnglishVersion(
  data: { titleEn?: string; bodyEn?: string },
  context: z.RefinementCtx
) {
  if (data.titleEn === undefined || data.bodyEn === undefined) return
  if ((data.titleEn === "") === (data.bodyEn === "")) return

  context.addIssue({
    code: "custom",
    message: message("translationIncomplete"),
    path: [data.titleEn === "" ? "titleEn" : "bodyEn"],
  })
}

export const createArticleSchema = articleFields.superRefine((data, context) =>
  refineEnglishVersion(
    { titleEn: data.titleEn ?? "", bodyEn: data.bodyEn ?? "" },
    context
  )
)

export type CreateArticleInput = z.infer<typeof createArticleSchema>
