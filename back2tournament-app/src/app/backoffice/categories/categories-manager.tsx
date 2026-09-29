"use client"

import { PlusIcon } from "lucide-react"
import { useRouter } from "next/navigation"
import { useTranslations } from "next-intl"
import { useState, type FormEvent } from "react"
import { CopyButton } from "@/components/copy-button"
import { Button } from "@/components/ui/button"
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
  FieldDescription,
  FieldError,
  FieldGroup,
  FieldLabel,
} from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import { Spinner } from "@/components/ui/spinner"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { toast } from "@/components/ui/toast"
import { useCreateCategory } from "@/features/blog/hooks/use-create-category"
import { slugify } from "@/features/blog/lib/slugify"
import { createCategorySchema } from "@/features/blog/schemas/create-category.schema"
import type { Category } from "@/features/blog/types"
import { useApiErrorMessage } from "@/hooks/use-api-error-message"
import { useFieldErrors } from "@/hooks/use-field-errors"
import { ListCard } from "../list-card"
import { LoadError } from "../load-error"

export function CategoriesManager({
  categories,
}: {
  categories: Category[] | null
}) {
  const t = useTranslations("backoffice.categories")
  const router = useRouter()
  const describeError = useApiErrorMessage()
  const createCategory = useCreateCategory()
  const [name, setName] = useState("")
  const [customSlug, setCustomSlug] = useState<string | null>(null)

  const slug = customSlug ?? slugify(name)
  const parsed = createCategorySchema.safeParse({ name, slug })
  const fieldErrors = useFieldErrors<"name" | "slug">(parsed)

  function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (!parsed.success) {
      fieldErrors.reveal()
      return
    }

    createCategory.mutate(parsed.data, {
      onSuccess: (category) => {
        toast.add({
          type: "success",
          title: t("success"),
          description: t("successDescription", {
            slug: category.slug ?? parsed.data.slug,
          }),
        })
        setName("")
        setCustomSlug(null)
        fieldErrors.hide()
        router.refresh()
      },
      onError: (error) => {
        toast.add({
          type: "error",
          title: t("error"),
          description: describeError(error, { 403: t("errors.forbidden") }),
        })
      },
    })
  }

  return (
    <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)]">
      <Card>
        <CardHeader>
          <CardTitle>{t("formTitle")}</CardTitle>
          <CardDescription>{t("formDescription")}</CardDescription>
        </CardHeader>
        <CardContent>
          <form id="create-category-form" onSubmit={onSubmit} noValidate>
            <FieldGroup>
              <Field data-invalid={fieldErrors.isInvalid("name")}>
                <FieldLabel htmlFor="category-name">{t("name")}</FieldLabel>
                <Input
                  id="category-name"
                  value={name}
                  onChange={(event) => setName(event.target.value)}
                  placeholder={t("namePlaceholder")}
                  aria-invalid={fieldErrors.isInvalid("name")}
                />
                <FieldError errors={fieldErrors.messagesFor("name")} />
              </Field>
              <Field data-invalid={fieldErrors.isInvalid("slug")}>
                <FieldLabel htmlFor="category-slug">{t("slug")}</FieldLabel>
                <Input
                  id="category-slug"
                  value={slug}
                  onChange={(event) => setCustomSlug(event.target.value)}
                  placeholder={t("slugPlaceholder")}
                  autoComplete="off"
                  spellCheck={false}
                  aria-invalid={fieldErrors.isInvalid("slug")}
                />
                <FieldDescription>{t("slugDescription")}</FieldDescription>
                <FieldError errors={fieldErrors.messagesFor("slug")} />
              </Field>
            </FieldGroup>
          </form>
        </CardContent>
        <CardFooter>
          <Button
            type="submit"
            form="create-category-form"
            disabled={createCategory.isPending}
          >
            {createCategory.isPending ? (
              <Spinner data-icon="inline-start" />
            ) : (
              <PlusIcon data-icon="inline-start" />
            )}
            {t("submit")}
          </Button>
        </CardFooter>
      </Card>
      {categories === null ? (
        <LoadError />
      ) : (
        <ListCard
          title={t("list.title")}
          description={t("list.description")}
          count={categories.length}
          emptyTitle={t("list.emptyTitle")}
          emptyDescription={t("list.emptyDescription")}
        >
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>{t("columns.name")}</TableHead>
                <TableHead>{t("columns.slug")}</TableHead>
                <TableHead>{t("columns.id")}</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {categories.map((category) => {
                if (!category.id) return null

                return (
                  <TableRow key={category.id}>
                    <TableCell className="font-medium">
                      {category.name}
                    </TableCell>
                    <TableCell>
                      <code className="font-mono text-xs">{category.slug}</code>
                    </TableCell>
                    <TableCell>
                      <div className="flex items-center gap-1">
                        <code className="font-mono text-xs">
                          {category.id.slice(0, 8)}
                        </code>
                        <CopyButton value={category.id} label={t("copyId")} />
                      </div>
                    </TableCell>
                  </TableRow>
                )
              })}
            </TableBody>
          </Table>
        </ListCard>
      )}
    </div>
  )
}
