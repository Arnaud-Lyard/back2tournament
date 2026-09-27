"use client"

import { LanguagesIcon } from "lucide-react"
import { useTranslations } from "next-intl"
import {
  Field,
  FieldDescription,
  FieldError,
  FieldLabel,
  FieldLegend,
  FieldSet,
} from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"

type EnglishField = "titleEn" | "bodyEn"

interface EnglishVersionFieldsProps {
  /** Keeps the ids of the create form and the edit form apart. */
  idPrefix: string
  titleEn: string
  bodyEn: string
  onTitleEnChange: (value: string) => void
  onBodyEnChange: (value: string) => void
  fieldErrors: {
    isInvalid: (field: EnglishField) => boolean
    messagesFor: (field: EnglishField) => { message: string }[] | undefined
  }
}

/** The optional English version of an article: its title and its body. */
export function EnglishVersionFields({
  idPrefix,
  titleEn,
  bodyEn,
  onTitleEnChange,
  onBodyEnChange,
  fieldErrors,
}: EnglishVersionFieldsProps) {
  const t = useTranslations("backoffice.articles.english")

  return (
    <FieldSet>
      <FieldLegend className="flex items-center gap-2">
        <LanguagesIcon className="size-4" />
        {t("legend")}
      </FieldLegend>
      <FieldDescription>{t("description")}</FieldDescription>
      <Field data-invalid={fieldErrors.isInvalid("titleEn")}>
        <FieldLabel htmlFor={`${idPrefix}-title-en`}>
          {t("titleLabel")}
        </FieldLabel>
        <Input
          id={`${idPrefix}-title-en`}
          lang="en"
          value={titleEn}
          onChange={(event) => onTitleEnChange(event.target.value)}
          placeholder={t("titlePlaceholder")}
          aria-invalid={fieldErrors.isInvalid("titleEn")}
        />
        <FieldError errors={fieldErrors.messagesFor("titleEn")} />
      </Field>
      <Field data-invalid={fieldErrors.isInvalid("bodyEn")}>
        <FieldLabel htmlFor={`${idPrefix}-body-en`}>
          {t("bodyLabel")}
        </FieldLabel>
        <Textarea
          id={`${idPrefix}-body-en`}
          lang="en"
          value={bodyEn}
          onChange={(event) => onBodyEnChange(event.target.value)}
          placeholder={t("bodyPlaceholder")}
          rows={8}
          aria-invalid={fieldErrors.isInvalid("bodyEn")}
        />
        <FieldError errors={fieldErrors.messagesFor("bodyEn")} />
      </Field>
    </FieldSet>
  )
}
