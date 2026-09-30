"use client"

import { CheckIcon, XIcon } from "lucide-react"
import { useRouter } from "next/navigation"
import { useTranslations } from "next-intl"
import { Button } from "@/components/ui/button"
import { Spinner } from "@/components/ui/spinner"
import { toast } from "@/components/ui/toast"
import { useAdmitMember } from "@/features/clans/hooks/use-admit-member"
import { useRemoveMember } from "@/features/clans/hooks/use-remove-member"
import { useApiErrorMessage } from "@/hooks/use-api-error-message"

interface JoinRequestActionsProps {
  clanId: string
  playerId: string
  battletag: string
}

export function JoinRequestActions({
  clanId,
  playerId,
  battletag,
}: JoinRequestActionsProps) {
  const t = useTranslations("clans.requests")
  const router = useRouter()
  const describeError = useApiErrorMessage()
  const admit = useAdmitMember()
  const decline = useRemoveMember()

  function onAdmit() {
    admit.mutate(
      { clanId, playerId },
      {
        onSuccess: () => {
          toast.add({ type: "success", title: t("admitted", { battletag }) })
          router.refresh()
        },
        onError: (error) => {
          toast.add({
            type: "error",
            title: t("error"),
            description: describeError(error, {
              404: t("errors.withdrawn"),
              409: t("errors.elsewhere"),
            }),
          })
          router.refresh()
        },
      }
    )
  }

  function onDecline() {
    decline.mutate(
      { clanId, playerId },
      {
        onSuccess: () => {
          toast.add({ type: "success", title: t("declined", { battletag }) })
          router.refresh()
        },
        onError: (error) => {
          toast.add({
            type: "error",
            title: t("error"),
            description: describeError(error, { 404: t("errors.withdrawn") }),
          })
          router.refresh()
        },
      }
    )
  }

  const pending = admit.isPending || decline.isPending

  return (
    <div className="flex flex-wrap gap-2">
      <Button
        size="sm"
        onClick={onAdmit}
        disabled={pending}
        aria-label={t("admitLabel", { battletag })}
      >
        {admit.isPending ? (
          <Spinner data-icon="inline-start" />
        ) : (
          <CheckIcon data-icon="inline-start" />
        )}
        {t("admit")}
      </Button>
      <Button
        size="sm"
        variant="outline"
        onClick={onDecline}
        disabled={pending}
        aria-label={t("declineLabel", { battletag })}
      >
        {decline.isPending ? (
          <Spinner data-icon="inline-start" />
        ) : (
          <XIcon data-icon="inline-start" />
        )}
        {t("decline")}
      </Button>
    </div>
  )
}
