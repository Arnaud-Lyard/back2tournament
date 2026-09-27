"use client"

import { SaveIcon } from "lucide-react"
import Link from "next/link"
import { useRouter } from "next/navigation"
import { useTranslations } from "next-intl"
import { useState, type FormEvent } from "react"
import { Button, buttonVariants } from "@/components/ui/button"
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import {
  Field,
  FieldError,
  FieldGroup,
  FieldLabel,
} from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import { NativeSelect, NativeSelectOption } from "@/components/ui/native-select"
import { Spinner } from "@/components/ui/spinner"
import { Textarea } from "@/components/ui/textarea"
import { toast } from "@/components/ui/toast"
import { useUpdateArticle } from "@/features/blog/hooks/use-update-article"
import { updateArticleSchema } from "@/features/blog/schemas/update-article.schema"
import type { ArticleSummary, Category } from "@/features/blog/types"
import { useApiErrorMessage } from "@/hooks/use-api-error-message"
import { useFieldErrors } from "@/hooks/use-field-errors"
import { cn } from "@/libs/utils"

interface EditArticleFormProps {
  articleId: string
  article: ArticleSummary
  categories: Category[]
  /** Where the form leaves to, saved or cancelled: the article's preview. */
  previewHref: string
}

/** Changes the title, the category or the body; the status and the author stay as they are. */
export function EditArticleForm({
  articleId,
  article,
  categories,
  previewHref,
}: EditArticleFormProps) {
  const t = useTranslations("backoffice.articles")
  const router = useRouter()
  const describeError = useApiErrorMessage()
  const updateArticle = useUpdateArticle()
  const [title, setTitle] = useState(article.title ?? "")
  const [categorySlug, setCategorySlug] = useState(
    categories.find((category) => category.id === article.category?.value)
      ?.slug ?? ""
  )
  const [body, setBody] = useState(article.body ?? "")
  const parsed = updateArticleSchema.safeParse({ title, categorySlug, body })
  const fieldErrors = useFieldErrors<"title" | "categorySlug" | "body">(parsed)

  function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (!parsed.success) {
      fieldErrors.reveal()
      return
    }

    updateArticle.mutate(
      { id: articleId, ...parsed.data },
      {
        onSuccess: () => {
          toast.add({ type: "success", title: t("edit.success") })
          router.push(previewHref, { scroll: false })
          router.refresh()
        },
        onError: (error) => {
          toast.add({
            type: "error",
            title: t("edit.error"),
            description: describeError(error, {
              403: t("edit.errors.forbidden"),
              404: t("edit.errors.notFound"),
            }),
          })
        },
      }
    )
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>{t("edit.title")}</CardTitle>
        <CardDescription>{t("edit.description")}</CardDescription>
      </CardHeader>
      <CardContent>
        <form id="edit-article-form" onSubmit={onSubmit} noValidate>
          <FieldGroup>
            <Field data-invalid={fieldErrors.isInvalid("title")}>
              <FieldLabel htmlFor="edit-article-title">
                {t("titleLabel")}
              </FieldLabel>
              <Input
                id="edit-article-title"
                value={title}
                onChange={(event) => setTitle(event.target.value)}
                aria-invalid={fieldErrors.isInvalid("title")}
              />
              <FieldError errors={fieldErrors.messagesFor("title")} />
            </Field>
            <Field data-invalid={fieldErrors.isInvalid("categorySlug")}>
              <FieldLabel htmlFor="edit-article-category">
                {t("category")}
              </FieldLabel>
              <NativeSelect
                id="edit-article-category"
                value={categorySlug}
                onChange={(event) => setCategorySlug(event.target.value)}
                aria-invalid={fieldErrors.isInvalid("categorySlug")}
              >
                <NativeSelectOption value="" disabled>
                  {t("categoryPlaceholder")}
                </NativeSelectOption>
                {categories.map((category) =>
                  category.slug ? (
                    <NativeSelectOption
                      key={category.slug}
                      value={category.slug}
                    >
                      {category.name}
                    </NativeSelectOption>
                  ) : null
                )}
              </NativeSelect>
              <FieldError errors={fieldErrors.messagesFor("categorySlug")} />
            </Field>
            <Field data-invalid={fieldErrors.isInvalid("body")}>
              <FieldLabel htmlFor="edit-article-body">{t("body")}</FieldLabel>
              <Textarea
                id="edit-article-body"
                value={body}
                onChange={(event) => setBody(event.target.value)}
                rows={12}
                aria-invalid={fieldErrors.isInvalid("body")}
              />
              <FieldError errors={fieldErrors.messagesFor("body")} />
            </Field>
          </FieldGroup>
        </form>
      </CardContent>
      <CardFooter className="flex-wrap gap-2">
        <Button
          type="submit"
          form="edit-article-form"
          disabled={updateArticle.isPending}
        >
          {updateArticle.isPending ? (
            <Spinner data-icon="inline-start" />
          ) : (
            <SaveIcon data-icon="inline-start" />
          )}
          {t("edit.submit")}
        </Button>
        <Link
          href={previewHref}
          scroll={false}
          className={cn(buttonVariants({ variant: "ghost" }))}
        >
          {t("edit.cancel")}
        </Link>
      </CardFooter>
    </Card>
  )
}
