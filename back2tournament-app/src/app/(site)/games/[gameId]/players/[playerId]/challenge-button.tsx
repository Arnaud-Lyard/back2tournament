"use client"

import { CircleCheckIcon, SwordsIcon } from "lucide-react"
import Link from "next/link"
import { useRouter } from "next/navigation"
import { useTranslations } from "next-intl"
import { useState } from "react"
import { Button, buttonVariants } from "@/components/ui/button"
import { Spinner } from "@/components/ui/spinner"
import { toast } from "@/components/ui/toast"
import { useAuth } from "@/features/auth/hooks/use-auth"
import { useCreateFight } from "@/features/fights/hooks/use-create-fight"
import { useApiErrorMessage } from "@/hooks/use-api-error-message"
import { cn } from "@/libs/utils"

interface ChallengeButtonProps {
  gameId: string
  playerId: string
  battletag: string
}

export function ChallengeButton({
  gameId,
  playerId,
  battletag,
}: ChallengeButtonProps) {
  const t = useTranslations("games.challenge")
  const router = useRouter()
  const { isAuthenticated, playerFor } = useAuth()
  const describeError = useApiErrorMessage()
  const createFight = useCreateFight()
  const [sent, setSent] = useState(false)

  if (!isAuthenticated) {
    return (
      <Hint text={t("signInPrompt")}>
        <Link
          href="/login"
          className={cn(buttonVariants({ variant: "outline" }))}
        >
          {t("signIn")}
        </Link>
      </Hint>
    )
  }

  const mine = playerFor(gameId)

  if (!mine) {
    return (
      <Hint text={t("noProfile")}>
        <Link
          href={`/players/new?gameId=${encodeURIComponent(gameId)}`}
          className={cn(buttonVariants({ variant: "outline" }))}
        >
          {t("createProfile")}
        </Link>
      </Hint>
    )
  }

  if (mine.id === playerId) {
    return <Hint text={t("itIsYou")} />
  }

  if (sent) {
    return (
      <p className="inline-flex items-center gap-2 text-sm font-medium text-primary">
        <CircleCheckIcon className="size-4" />
        {t("sentDescription", { battletag })}
      </p>
    )
  }

  function challenge() {
    if (!mine) return

    createFight.mutate(
      { playerOne: mine.id, playerTwo: playerId },
      {
        onSuccess: () => {
          toast.add({
            type: "success",
            title: t("sent"),
            description: t("sentDescription", { battletag }),
          })
          setSent(true)
          router.refresh()
        },
        onError: (error) => {
          toast.add({
            type: "error",
            title: t("error"),
            description: describeError(error, {
              400: t("errors.alreadyOpen"),
              403: t("errors.notYours"),
              404: t("errors.gone"),
            }),
          })
        },
      }
    )
  }

  return (
    <Button onClick={challenge} disabled={createFight.isPending}>
      {createFight.isPending ? (
        <Spinner data-icon="inline-start" />
      ) : (
        <SwordsIcon data-icon="inline-start" />
      )}
      {t("challenge", { battletag })}
    </Button>
  )
}

function Hint({
  text,
  children,
}: {
  text: string
  children?: React.ReactNode
}) {
  return (
    <div className="flex flex-wrap items-center gap-3">
      <p className="text-sm text-muted-foreground">{text}</p>
      {children}
    </div>
  )
}
