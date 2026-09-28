"use client"

import { CircleCheckIcon } from "lucide-react"
import { useRouter } from "next/navigation"
import { useTranslations } from "next-intl"
import { useState, type FormEvent } from "react"
import { Button } from "@/components/ui/button"
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import {
  Field,
  FieldDescription,
  FieldError,
  FieldGroup,
  FieldLabel,
} from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import { NativeSelect, NativeSelectOption } from "@/components/ui/native-select"
import { Spinner } from "@/components/ui/spinner"
import { toast } from "@/components/ui/toast"
import { useAuth } from "@/features/auth/hooks/use-auth"
import { useCreatePlayer } from "@/features/players/hooks/use-create-player"
import { createPlayerSchema } from "@/features/players/schemas/create-player.schema"
import type { Game } from "@/features/games/types"
import type { CreatedPlayer } from "@/features/players/types"
import { useApiErrorMessage } from "@/hooks/use-api-error-message"
import { useFieldErrors } from "@/hooks/use-field-errors"

export function CreatePlayerForm({
  games,
  defaultGameId,
}: {
  games: Game[]
  defaultGameId?: string
}) {
  const t = useTranslations("players")
  const router = useRouter()
  const { playerFor } = useAuth()
  const describeError = useApiErrorMessage()
  const createPlayer = useCreatePlayer()
  const [game, setGame] = useState(
    defaultGameId && !playerFor(defaultGameId) ? defaultGameId : ""
  )
  const [battletag, setBattletag] = useState("")
  const [player, setPlayer] = useState<CreatedPlayer | null>(null)
  const parsed = createPlayerSchema.safeParse({ game, battletag })
  const fieldErrors = useFieldErrors<"game" | "battletag">(parsed)

  function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (!parsed.success) {
      fieldErrors.reveal()
      return
    }

    createPlayer.mutate(parsed.data, {
      onSuccess: (created) => {
        toast.add({ type: "success", title: t("success") })
        setPlayer(created)
        router.refresh()
      },
      onError: (error) => {
        toast.add({
          type: "error",
          title: t("error"),
          description: describeError(error, {
            404: t("errors.gameNotFound"),
            409: t("errors.alreadyExists"),
          }),
        })
      },
    })
  }

  if (player) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>{t("success")}</CardTitle>
          <CardDescription>
            {t("successDescription", { battletag: player.battletag ?? "" })}
          </CardDescription>
          <CardAction>
            <CircleCheckIcon />
          </CardAction>
        </CardHeader>
      </Card>
    )
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>{t("formTitle")}</CardTitle>
        <CardDescription>{t("formDescription")}</CardDescription>
      </CardHeader>
      <CardContent>
        <form id="create-player-form" onSubmit={onSubmit} noValidate>
          <FieldGroup>
            <Field data-invalid={fieldErrors.isInvalid("game")}>
              <FieldLabel htmlFor="player-game">{t("game")}</FieldLabel>
              <NativeSelect
                id="player-game"
                value={game}
                onChange={(event) => setGame(event.target.value)}
                aria-invalid={fieldErrors.isInvalid("game")}
              >
                <NativeSelectOption value="" disabled>
                  {t("gamePlaceholder")}
                </NativeSelectOption>
                {games.map((option) => {
                  const id = option.id?.value
                  if (!id) return null

                  const profile = playerFor(id)
                  return (
                    <NativeSelectOption
                      key={id}
                      value={id}
                      disabled={!!profile}
                    >
                      {profile
                        ? t("alreadyRegistered", {
                            title: option.title ?? "",
                            battletag: profile.battletag,
                          })
                        : option.title}
                    </NativeSelectOption>
                  )
                })}
              </NativeSelect>
              <FieldDescription>{t("gameDescription")}</FieldDescription>
              <FieldError errors={fieldErrors.messagesFor("game")} />
            </Field>
            <Field data-invalid={fieldErrors.isInvalid("battletag")}>
              <FieldLabel htmlFor="player-battletag">
                {t("battletag")}
              </FieldLabel>
              <Input
                id="player-battletag"
                value={battletag}
                onChange={(event) => setBattletag(event.target.value)}
                placeholder={t("battletagPlaceholder")}
                autoComplete="off"
                aria-invalid={fieldErrors.isInvalid("battletag")}
              />
              <FieldDescription>{t("battletagDescription")}</FieldDescription>
              <FieldError errors={fieldErrors.messagesFor("battletag")} />
            </Field>
          </FieldGroup>
        </form>
      </CardContent>
      <CardFooter>
        <Button
          type="submit"
          form="create-player-form"
          disabled={createPlayer.isPending}
        >
          {createPlayer.isPending && <Spinner data-icon="inline-start" />}
          {t("submit")}
        </Button>
      </CardFooter>
    </Card>
  )
}
