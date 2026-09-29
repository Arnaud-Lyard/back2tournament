"use client"

import { useTranslations } from "next-intl"
import { useCallback } from "react"
import { ApiError, type ApiErrorCode } from "@/libs/api/errors"

type SpecificWording = Partial<Record<number | ApiErrorCode, string>>

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
