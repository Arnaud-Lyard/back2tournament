"use client"

import { UserMinusIcon } from "lucide-react"
import { useRouter } from "next/navigation"
import { useTranslations } from "next-intl"
import { Button } from "@/components/ui/button"
import { Spinner } from "@/components/ui/spinner"
import { toast } from "@/components/ui/toast"
import { useRemoveMember } from "@/features/clans/hooks/use-remove-member"
import { useApiErrorMessage } from "@/hooks/use-api-error-message"

interface RemoveMemberButtonProps {
  clanId: string
  playerId: string
  battletag: string
  invited: boolean
}

/** The leader lets a member go, or withdraws an invitation. */
export function RemoveMemberButton({
  clanId,
  playerId,
  battletag,
  invited,
}: RemoveMemberButtonProps) {
  const t = useTranslations("clans.members")
  const router = useRouter()
  const describeError = useApiErrorMessage()
  const remove = useRemoveMember()

  function onClick() {
    remove.mutate(
      { clanId, playerId },
      {
        onSuccess: () => {
          toast.add({
            type: "success",
            title: t(invited ? "withdrawn" : "removed", { battletag }),
          })
          router.refresh()
        },
        onError: (error) => {
          toast.add({
            type: "error",
            title: t("removeError"),
            description: describeError(error, { 409: t("errors.inTeam") }),
          })
        },
      }
    )
  }

  return (
    <Button
      variant="ghost"
      size="sm"
      onClick={onClick}
      disabled={remove.isPending}
      aria-label={t(invited ? "withdrawLabel" : "removeLabel", { battletag })}
    >
      {remove.isPending ? (
        <Spinner data-icon="inline-start" />
      ) : (
        <UserMinusIcon data-icon="inline-start" />
      )}
      {t(invited ? "withdraw" : "remove")}
    </Button>
  )
}
