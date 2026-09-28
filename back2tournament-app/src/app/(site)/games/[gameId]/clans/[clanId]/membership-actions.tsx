"use client"

import { CheckIcon, LogOutIcon, XIcon } from "lucide-react"
import { useRouter } from "next/navigation"
import { useTranslations } from "next-intl"
import { Button } from "@/components/ui/button"
import { Spinner } from "@/components/ui/spinner"
import { toast } from "@/components/ui/toast"
import { useJoinClan } from "@/features/clans/hooks/use-join-clan"
import { useRemoveMember } from "@/features/clans/hooks/use-remove-member"
import { useApiErrorMessage } from "@/hooks/use-api-error-message"

interface MembershipActionsProps {
  clanId: string
  playerId: string
  status: "invited" | "active"
}

export function MembershipActions({
  clanId,
  playerId,
  status,
}: MembershipActionsProps) {
  const t = useTranslations("clans.membership")
  const router = useRouter()
  const describeError = useApiErrorMessage()
  const join = useJoinClan()
  const remove = useRemoveMember()

  function accept() {
    join.mutate(clanId, {
      onSuccess: () => {
        toast.add({ type: "success", title: t("joined") })
        router.refresh()
      },
      onError: (error) => {
        toast.add({
          type: "error",
          title: t("error"),
          description: describeError(error, { 409: t("errors.elsewhere") }),
        })
      },
    })
  }

  function leave() {
    remove.mutate(
      { clanId, playerId },
      {
        onSuccess: () => {
          toast.add({
            type: "success",
            title: status === "invited" ? t("declined") : t("left"),
          })
          router.refresh()
        },
        onError: (error) => {
          toast.add({
            type: "error",
            title: t("error"),
            description: describeError(error, { 409: t("errors.cannotLeave") }),
          })
        },
      }
    )
  }

  const pending = join.isPending || remove.isPending

  if (status === "invited") {
    return (
      <div className="flex flex-wrap gap-2">
        <Button onClick={accept} disabled={pending}>
          {join.isPending ? (
            <Spinner data-icon="inline-start" />
          ) : (
            <CheckIcon data-icon="inline-start" />
          )}
          {t("accept")}
        </Button>
        <Button variant="outline" onClick={leave} disabled={pending}>
          {remove.isPending ? (
            <Spinner data-icon="inline-start" />
          ) : (
            <XIcon data-icon="inline-start" />
          )}
          {t("decline")}
        </Button>
      </div>
    )
  }

  return (
    <Button variant="outline" onClick={leave} disabled={pending}>
      {remove.isPending ? (
        <Spinner data-icon="inline-start" />
      ) : (
        <LogOutIcon data-icon="inline-start" />
      )}
      {t("leave")}
    </Button>
  )
}
