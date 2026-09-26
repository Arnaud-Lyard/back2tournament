"use client"

import { useTranslations } from "next-intl"
import { useCallback, useState } from "react"
import { z } from "zod"
import type { ValidationMessage } from "@/libs/validation"

interface ParseResult {
  success: boolean
  error?: z.ZodError
}

/**
 * Per-field errors for a form validated with one of the shared Zod schemas.
 * Pass the schema's result for the current values: errors stay hidden until
 * `reveal()` (a first submit), then follow every keystroke, so a fixed field
 * clears at once. Schema messages are `validation.*` keys, translated here;
 * `messagesFor` returns what `<FieldError errors>` expects.
 */
export function useFieldErrors<TField extends string>(result: ParseResult) {
  const t = useTranslations("validation")
  const [revealed, setRevealed] = useState(false)

  const errors: Partial<Record<TField, string[]>> =
    revealed && result.error
      ? (z.flattenError(result.error).fieldErrors as Partial<
          Record<TField, string[]>
        >)
      : {}

  const reveal = useCallback(() => setRevealed(true), [])
  const hide = useCallback(() => setRevealed(false), [])

  const messagesFor = (field: TField) =>
    errors[field]?.map((key) => ({ message: t(key as ValidationMessage) }))

  const isInvalid = (field: TField) => Boolean(errors[field]?.length)

  return { reveal, hide, messagesFor, isInvalid }
}
