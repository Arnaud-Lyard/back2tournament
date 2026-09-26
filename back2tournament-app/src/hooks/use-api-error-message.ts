"use client"

import { useTranslations } from "next-intl"
import { useCallback } from "react"
import { ApiError, type ApiErrorCode } from "@/libs/api/errors"

type SpecificWording = Partial<Record<number | ApiErrorCode, string>>

/**
 * Turns a failed call into a sentence for the user, in their language. A
 * status means something different per action (a 403 on confirmation is not
 * a 403 on creation), so callers pass their own wording by code or status —
 * a code wins, being the more precise; the rest gets a shared one. The
 * backend's English message is never shown: fetchJson logs it to the console.
 */
export function useApiErrorMessage() {
  const t = useTranslations("apiErrors")

  return useCallback(
    (error: unknown, specific: SpecificWording = {}) => {
      if (!(error instanceof ApiError)) return t("unknown")

      const wording =
        (error.code && specific[error.code]) ?? specific[error.status]
      if (wording) return wording

      if (error.status === 400) return t("invalid")
      if (error.status === 401) return t("sessionExpired")
      if (error.status === 403) return t("forbidden")
      if (error.status === 404) return t("notFound")
      if (error.status === 409) return t("conflict")
      if (error.status >= 500) return t("unavailable")
      return t("unknown")
    },
    [t]
  )
}
