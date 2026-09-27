"use client"

import { CircleCheckIcon, EyeIcon, SendIcon } from "lucide-react"
import Link from "next/link"
import { useRouter } from "next/navigation"
import { useTranslations } from "next-intl"
import { useState, type FormEvent } from "react"
import { Button, buttonVariants } from "@/components/ui/button"
import { cn } from "@/libs/utils"
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import {
  Field,
  FieldDescription,
  FieldError,
  FieldGroup,
  FieldLabel,
} from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import { NativeSelect, NativeSelectOption } from "@/components/ui/native-select"
import { Spinner } from "@/components/ui/spinner"
import { Textarea } from "@/components/ui/textarea"
import { toast } from "@/components/ui/toast"
import { useCreateArticle } from "@/features/blog/hooks/use-create-article"
import { createArticleSchema } from "@/features/blog/schemas/create-article.schema"
import type { Category } from "@/features/blog/types"
import { useApiErrorMessage } from "@/hooks/use-api-error-message"
import { useFieldErrors } from "@/hooks/use-field-errors"
import { EnglishVersionFields } from "./english-version-fields"

export function CreateArticleForm({ categories }: { categories: Category[] }) {
  const t = useTranslations("backoffice.articles")
  const router = useRouter()
  const describeError = useApiErrorMessage()
  const createArticle = useCreateArticle()
  const [title, setTitle] = useState("")
  const [categorySlug, setCategorySlug] = useState("")
  const [body, setBody] = useState("")
  const [titleEn, setTitleEn] = useState("")
  const [bodyEn, setBodyEn] = useState("")
  const [articleId, setArticleId] = useState<string | null>(null)
  const parsed = createArticleSchema.safeParse({
    title,
    categorySlug,
    body,
    titleEn,
    bodyEn,
  })
  const fieldErrors = useFieldErrors<
    "title" | "categorySlug" | "body" | "titleEn" | "bodyEn"
  >(parsed)

  function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (!parsed.success) {
      fieldErrors.reveal()
      return
    }

    createArticle.mutate(parsed.data, {
      onSuccess: (article) => {
        toast.add({ type: "success", title: t("success") })
        setArticleId(article.id?.value ?? null)
        router.refresh()
      },
      onError: (error) => {
        toast.add({
          type: "error",
          title: t("error"),
          description: describeError(error, {
            403: t("errors.forbidden"),
            404: t("errors.categoryNotFound"),
          }),
        })
      },
    })
  }

  function startOver() {
    setArticleId(null)
    setTitle("")
    setBody("")
    setTitleEn("")
    setBodyEn("")
    fieldErrors.hide()
    createArticle.reset()
  }

  if (articleId) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>{t("success")}</CardTitle>
          <CardDescription>{t("successDescription")}</CardDescription>
          <CardAction>
            <CircleCheckIcon />
          </CardAction>
        </CardHeader>
        <CardFooter className="flex-wrap gap-2">
          <Link
            href={`/backoffice/articles?articleId=${encodeURIComponent(articleId)}`}
            scroll={false}
            className={cn(buttonVariants())}
          >
            <EyeIcon data-icon="inline-start" />
            {t("view")}
          </Link>
          <Button variant="outline" onClick={startOver}>
            {t("writeAnother")}
          </Button>
        </CardFooter>
      </Card>
    )
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>{t("formTitle")}</CardTitle>
        <CardDescription>{t("formDescription")}</CardDescription>
      </CardHeader>
      <CardContent>
        <form id="create-article-form" onSubmit={onSubmit} noValidate>
          <FieldGroup>
            <Field data-invalid={fieldErrors.isInvalid("title")}>
              <FieldLabel htmlFor="article-title">{t("titleLabel")}</FieldLabel>
              <Input
                id="article-title"
                value={title}
                onChange={(event) => setTitle(event.target.value)}
                placeholder={t("titlePlaceholder")}
                aria-invalid={fieldErrors.isInvalid("title")}
              />
              <FieldError errors={fieldErrors.messagesFor("title")} />
            </Field>
            <Field data-invalid={fieldErrors.isInvalid("categorySlug")}>
              <FieldLabel htmlFor="article-category">
                {t("category")}
              </FieldLabel>
              <NativeSelect
                id="article-category"
                value={categorySlug}
                onChange={(event) => setCategorySlug(event.target.value)}
                aria-invalid={fieldErrors.isInvalid("categorySlug")}
              >
                <NativeSelectOption value="" disabled>
                  {categories.length === 0
                    ? t("noCategories")
                    : t("categoryPlaceholder")}
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
              <FieldDescription>{t("categoryDescription")}</FieldDescription>
              <FieldError errors={fieldErrors.messagesFor("categorySlug")} />
            </Field>
            <Field data-invalid={fieldErrors.isInvalid("body")}>
              <FieldLabel htmlFor="article-body">{t("body")}</FieldLabel>
              <Textarea
                id="article-body"
                value={body}
                onChange={(event) => setBody(event.target.value)}
                placeholder={t("bodyPlaceholder")}
                rows={10}
                aria-invalid={fieldErrors.isInvalid("body")}
              />
              <FieldError errors={fieldErrors.messagesFor("body")} />
            </Field>
            <EnglishVersionFields
              idPrefix="article"
              titleEn={titleEn}
              bodyEn={bodyEn}
              onTitleEnChange={setTitleEn}
              onBodyEnChange={setBodyEn}
              fieldErrors={fieldErrors}
            />
          </FieldGroup>
        </form>
      </CardContent>
      <CardFooter>
        <Button
          type="submit"
          form="create-article-form"
          disabled={createArticle.isPending}
        >
          {createArticle.isPending ? (
            <Spinner data-icon="inline-start" />
          ) : (
            <SendIcon data-icon="inline-start" />
          )}
          {t("submit")}
        </Button>
      </CardFooter>
    </Card>
  )
}
