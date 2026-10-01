"use client"

import { useRouter } from "next/navigation"
import { useTranslations } from "next-intl"
import { PasswordConfirmation } from "@/components/password-confirmation"
import { toast } from "@/components/ui/toast"
import { useDeleteAccount } from "@/features/auth/hooks/use-delete-account"
import { useApiErrorMessage } from "@/hooks/use-api-error-message"

export function DeleteAccountForm() {
  const t = useTranslations("account.deletion")
  const router = useRouter()
  const describeError = useApiErrorMessage()
  const deletion = useDeleteAccount()

  function onConfirm(password: string) {
    deletion.mutate(password, {
      onSuccess: () => {
        toast.add({ type: "success", title: t("done") })
        router.replace("/")
        router.refresh()
      },
      onError: (error) => {
        toast.add({
          type: "error",
          title: t("error"),
          description: describeError(error, {
            wrongPassword: t("errors.wrongPassword"),
            accountLeadsClan: t("errors.leadsClan"),
            accountOrganizesTournament: t("errors.organizesTournament"),
          }),
        })
      },
    })
  }

  return (
    <PasswordConfirmation
      trigger={t("trigger")}
      confirm={t("confirm")}
      pending={deletion.isPending}
      onConfirm={onConfirm}
    />
  )
}
