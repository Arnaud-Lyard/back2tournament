"use client"

import { SwordsIcon } from "lucide-react"
import { useRouter } from "next/navigation"
import { useTranslations } from "next-intl"
import { useState } from "react"
import { Button } from "@/components/ui/button"
import { Spinner } from "@/components/ui/spinner"
import { toast } from "@/components/ui/toast"
import { useCreateFight } from "@/features/fights/hooks/use-create-fight"
import { useApiErrorMessage } from "@/hooks/use-api-error-message"

interface ChallengeTeamButtonProps {
  /** A team the caller leads, of the same format. */
  myTeamId: string
  myTeamName: string
  theirTeamId: string
  theirTeamName: string
}

export function ChallengeTeamButton({
  myTeamId,
  myTeamName,
  theirTeamId,
  theirTeamName,
}: ChallengeTeamButtonProps) {
  const t = useTranslations("clans.teams")
  const router = useRouter()
  const describeError = useApiErrorMessage()
  const createFight = useCreateFight()
  const [sent, setSent] = useState(false)

  if (sent) {
    return (
      <p className="text-sm font-medium text-primary">{t("challengeSent")}</p>
    )
  }

  function onClick() {
    createFight.mutate(
      { teamOne: myTeamId, teamTwo: theirTeamId },
      {
        onSuccess: () => {
          toast.add({
            type: "success",
            title: t("challengeSent"),
            description: t("challengeSentDescription", { team: theirTeamName }),
          })
          setSent(true)
          router.refresh()
        },
        onError: (error) => {
          toast.add({
            type: "error",
            title: t("challengeError"),
            description: describeError(error, { 400: t("errors.cannotFight") }),
          })
        },
      }
    )
  }

  return (
    <Button
      size="sm"
      variant="outline"
      onClick={onClick}
      disabled={createFight.isPending}
    >
      {createFight.isPending ? (
        <Spinner data-icon="inline-start" />
      ) : (
        <SwordsIcon data-icon="inline-start" />
      )}
      {t("challengeWith", { team: myTeamName })}
    </Button>
  )
}
