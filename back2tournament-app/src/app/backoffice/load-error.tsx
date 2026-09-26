import { useTranslations } from "next-intl"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"

/** Stands in for a backoffice list the API could not return. */
export function LoadError() {
  const t = useTranslations("backoffice.loadError")

  return (
    <Alert variant="destructive">
      <AlertTitle>{t("title")}</AlertTitle>
      <AlertDescription>{t("description")}</AlertDescription>
    </Alert>
  )
}
