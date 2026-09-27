"use client"

import { Trash2Icon } from "lucide-react"
import { useRouter } from "next/navigation"
import { useTranslations } from "next-intl"
import { Button } from "@/components/ui/button"
import { Spinner } from "@/components/ui/spinner"
import { toast } from "@/components/ui/toast"
import { useDisbandTeam } from "@/features/teams/hooks/use-disband-team"
import { useApiErrorMessage } from "@/hooks/use-api-error-message"

export function DisbandTeamButton({
  teamId,
  name,
}: {
  teamId: string
  name: string
}) {
  const t = useTranslations("clans.teams")
  const router = useRouter()
  const describeError = useApiErrorMessage()
  const disband = useDisbandTeam()

  function onClick() {
    disband.mutate(teamId, {
      onSuccess: () => {
        toast.add({ type: "success", title: t("disbanded", { name }) })
        router.refresh()
      },
      onError: (error) => {
        toast.add({
          type: "error",
          title: t("disbandError"),
          description: describeError(error, { 409: t("errors.competed") }),
        })
      },
    })
  }

  return (
    <Button
      variant="ghost"
      size="sm"
      onClick={onClick}
      disabled={disband.isPending}
      aria-label={t("disbandLabel", { name })}
    >
      {disband.isPending ? (
        <Spinner data-icon="inline-start" />
      ) : (
        <Trash2Icon data-icon="inline-start" />
      )}
      {t("disband")}
    </Button>
  )
}
