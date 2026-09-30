"use client"

import { UserPlusIcon } from "lucide-react"
import { useRouter } from "next/navigation"
import { useTranslations } from "next-intl"
import { Button } from "@/components/ui/button"
import { Spinner } from "@/components/ui/spinner"
import { toast } from "@/components/ui/toast"
import { useRequestToJoin } from "@/features/clans/hooks/use-request-to-join"
import { useApiErrorMessage } from "@/hooks/use-api-error-message"

interface RequestToJoinButtonProps {
  clanId: string
}

export function RequestToJoinButton({ clanId }: RequestToJoinButtonProps) {
  const t = useTranslations("clans.membership")
  const router = useRouter()
  const describeError = useApiErrorMessage()
  const request = useRequestToJoin()

  function onClick() {
    request.mutate(clanId, {
      onSuccess: () => {
        toast.add({ type: "success", title: t("requestSent") })
        router.refresh()
      },
      onError: (error) => {
        toast.add({
          type: "error",
          title: t("error"),
          description: describeError(error, {
            409: t("errors.cannotRequest"),
          }),
        })
        router.refresh()
      },
    })
  }

  return (
    <Button onClick={onClick} disabled={request.isPending}>
      {request.isPending ? (
        <Spinner data-icon="inline-start" />
      ) : (
        <UserPlusIcon data-icon="inline-start" />
      )}
      {t("request")}
    </Button>
  )
}
