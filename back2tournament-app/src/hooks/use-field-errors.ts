"use client"

import { useTranslations } from "next-intl"
import { useCallback, useState } from "react"
import { z } from "zod"
import type { ValidationMessage } from "@/libs/validation"

interface ParseResult {
  success: boolean
  error?: z.ZodError
}

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
