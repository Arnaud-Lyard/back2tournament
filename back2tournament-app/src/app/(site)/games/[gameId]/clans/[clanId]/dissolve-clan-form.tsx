"use client"

import { useRouter } from "next/navigation"
import { useTranslations } from "next-intl"
import { PasswordConfirmation } from "@/components/password-confirmation"
import { toast } from "@/components/ui/toast"
import { useDissolveClan } from "@/features/clans/hooks/use-dissolve-clan"
import { useApiErrorMessage } from "@/hooks/use-api-error-message"

interface DissolveClanFormProps {
  clanId: string
  gameId: string
  clanName: string
}

export function DissolveClanForm({
  clanId,
  gameId,
  clanName,
}: DissolveClanFormProps) {
  const t = useTranslations("clans.dissolution")
  const router = useRouter()
  const describeError = useApiErrorMessage()
  const dissolution = useDissolveClan()

  function onConfirm(password: string) {
    dissolution.mutate(
      { clanId, password },
      {
        onSuccess: () => {
          toast.add({ type: "success", title: t("done", { name: clanName }) })
          router.replace(`/games/${encodeURIComponent(gameId)}/clans`)
          router.refresh()
        },
        onError: (error) => {
          toast.add({
            type: "error",
            title: t("error"),
            description: describeError(error, {
              wrongPassword: t("errors.wrongPassword"),
              403: t("errors.notLeader"),
              404: t("errors.gone"),
            }),
          })
        },
      }
    )
  }

  return (
    <PasswordConfirmation
      trigger={t("trigger")}
      confirm={t("confirm")}
      pending={dissolution.isPending}
      onConfirm={onConfirm}
    />
  )
}
