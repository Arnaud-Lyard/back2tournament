"use client"

import { CircleCheckIcon, UserPlusIcon } from "lucide-react"
import { useTranslations } from "next-intl"
import { useState } from "react"
import { Button } from "@/components/ui/button"
import { Spinner } from "@/components/ui/spinner"
import { toast } from "@/components/ui/toast"
import { useInvitePlayer } from "@/features/clans/hooks/use-invite-player"
import { useApiErrorMessage } from "@/hooks/use-api-error-message"

interface InviteToClanButtonProps {
  clanId: string
  clanName: string
  playerId: string
  battletag: string
}

export function InviteToClanButton({
  clanId,
  clanName,
  playerId,
  battletag,
}: InviteToClanButtonProps) {
  const t = useTranslations("games.invite")
  const describeError = useApiErrorMessage()
  const invite = useInvitePlayer()
  const [sent, setSent] = useState(false)

  if (sent) {
    return (
      <p className="inline-flex items-center gap-2 text-sm font-medium text-primary">
        <CircleCheckIcon className="size-4" />
        {t("sent", { battletag, clan: clanName })}
      </p>
    )
  }

  function onClick() {
    invite.mutate(
      { clanId, player: playerId },
      {
        onSuccess: () => {
          toast.add({
            type: "success",
            title: t("sent", { battletag, clan: clanName }),
          })
          setSent(true)
        },
        onError: (error) => {
          toast.add({
            type: "error",
            title: t("error"),
            description: describeError(error, {
              409: t("errors.alreadyThere"),
            }),
          })
        },
      }
    )
  }

  return (
    <Button variant="outline" onClick={onClick} disabled={invite.isPending}>
      {invite.isPending ? (
        <Spinner data-icon="inline-start" />
      ) : (
        <UserPlusIcon data-icon="inline-start" />
      )}
      {t("invite", { clan: clanName })}
    </Button>
  )
}
