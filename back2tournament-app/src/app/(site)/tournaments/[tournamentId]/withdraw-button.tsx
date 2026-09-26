"use client"

import { UserMinusIcon } from "lucide-react"
import { useRouter } from "next/navigation"
import { useTranslations } from "next-intl"
import { Button } from "@/components/ui/button"
import { Spinner } from "@/components/ui/spinner"
import { toast } from "@/components/ui/toast"
import { useWithdrawParticipant } from "@/features/tournaments/hooks/use-withdraw-participant"
import { useApiErrorMessage } from "@/hooks/use-api-error-message"

interface WithdrawButtonProps {
  tournamentId: string
  participantId: string
  name: string
}

export function WithdrawButton({
  tournamentId,
  participantId,
  name,
}: WithdrawButtonProps) {
  const t = useTranslations("tournaments.registration")
  const router = useRouter()
  const describeError = useApiErrorMessage()
  const withdraw = useWithdrawParticipant()

  function onClick() {
    withdraw.mutate(
      { tournamentId, participantId },
      {
        onSuccess: () => {
          toast.add({ type: "success", title: t("withdrawn", { name }) })
          router.refresh()
        },
        onError: (error) => {
          toast.add({
            type: "error",
            title: t("withdrawError"),
            description: describeError(error, { 409: t("errors.started") }),
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
      disabled={withdraw.isPending}
      aria-label={t("withdrawLabel", { name })}
    >
      {withdraw.isPending ? (
        <Spinner data-icon="inline-start" />
      ) : (
        <UserMinusIcon data-icon="inline-start" />
      )}
      {t("withdraw")}
    </Button>
  )
}
