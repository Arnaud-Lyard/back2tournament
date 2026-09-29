"use client"

import { BanIcon, PlayIcon } from "lucide-react"
import { useRouter } from "next/navigation"
import { useTranslations } from "next-intl"
import { Button } from "@/components/ui/button"
import { Spinner } from "@/components/ui/spinner"
import { toast } from "@/components/ui/toast"
import { useCancelTournament } from "@/features/tournaments/hooks/use-cancel-tournament"
import { useStartTournament } from "@/features/tournaments/hooks/use-start-tournament"
import type { TournamentStatus } from "@/features/tournaments/types"
import { useApiErrorMessage } from "@/hooks/use-api-error-message"

interface OrganizerActionsProps {
  tournamentId: string
  status: TournamentStatus
  participantCount: number
}

export function OrganizerActions({
  tournamentId,
  status,
  participantCount,
}: OrganizerActionsProps) {
  const t = useTranslations("tournaments.organizer")
  const router = useRouter()
  const describeError = useApiErrorMessage()
  const start = useStartTournament()
  const cancel = useCancelTournament()
  const pending = start.isPending || cancel.isPending

  function onStart() {
    start.mutate(tournamentId, {
      onSuccess: () => {
        toast.add({ type: "success", title: t("started") })
        router.refresh()
      },
      onError: (error) => {
        toast.add({
          type: "error",
          title: t("startError"),
          description: describeError(error, { 409: t("errors.cannotStart") }),
        })
      },
    })
  }

  function onCancel() {
    cancel.mutate(tournamentId, {
      onSuccess: () => {
        toast.add({ type: "success", title: t("cancelled") })
        router.refresh()
      },
      onError: (error) => {
        toast.add({
          type: "error",
          title: t("cancelError"),
          description: describeError(error),
        })
      },
    })
  }

  if (status !== "upcoming" && status !== "ongoing") return null

  return (
    <div className="flex flex-wrap gap-2">
      {status === "upcoming" && (
        <Button onClick={onStart} disabled={pending || participantCount < 2}>
          {start.isPending ? (
            <Spinner data-icon="inline-start" />
          ) : (
            <PlayIcon data-icon="inline-start" />
          )}
          {t("start")}
        </Button>
      )}
      <Button variant="outline" onClick={onCancel} disabled={pending}>
        {cancel.isPending ? (
          <Spinner data-icon="inline-start" />
        ) : (
          <BanIcon data-icon="inline-start" />
        )}
        {t("cancel")}
      </Button>
    </div>
  )
}
