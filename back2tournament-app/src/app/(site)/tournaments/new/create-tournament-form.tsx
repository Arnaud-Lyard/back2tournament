"use client"

import { useRouter } from "next/navigation"
import { useTranslations } from "next-intl"
import { useState, type FormEvent } from "react"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardFooter } from "@/components/ui/card"
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
import { formatLabel } from "@/features/fights/lib/challenge"
import type { Game } from "@/features/games/types"
import { useCreateTournament } from "@/features/tournaments/hooks/use-create-tournament"
import { toIsoWithOffset } from "@/features/tournaments/lib/date"
import { createTournamentSchema } from "@/features/tournaments/schemas/create-tournament.schema"
import { useApiErrorMessage } from "@/hooks/use-api-error-message"
import { useFieldErrors } from "@/hooks/use-field-errors"

type FormField = "name" | "game" | "teamSize" | "capacity" | "startsAt"

export function CreateTournamentForm({ games }: { games: Game[] }) {
  const t = useTranslations("tournaments.create")
  const router = useRouter()
  const describeError = useApiErrorMessage()
  const createTournament = useCreateTournament()
  const [name, setName] = useState("")
  const [gameId, setGameId] = useState("")
  const [teamSize, setTeamSize] = useState(1)
  const [capacity, setCapacity] = useState("8")
  const [startsAt, setStartsAt] = useState("")

  const game = games.find((option) => option.id?.value === gameId)
  const formats = game?.teamSizes?.length ? game.teamSizes : [1]
  const parsed = createTournamentSchema.safeParse({
    name,
    game: gameId,
    teamSize,
    capacity: capacity.trim() === "" ? undefined : Number(capacity),
    startsAt: toIsoWithOffset(startsAt),
  })
  const fieldErrors = useFieldErrors<FormField>(parsed)

  function onGameChange(id: string) {
    setGameId(id)
    const sizes = games.find((option) => option.id?.value === id)?.teamSizes
    setTeamSize(sizes?.[0] ?? 1)
  }

  function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (!parsed.success) {
      fieldErrors.reveal()
      return
    }

    createTournament.mutate(parsed.data, {
      onSuccess: (tournament) => {
        toast.add({
          type: "success",
          title: t("success", { name: tournament.name ?? parsed.data.name }),
        })
        const id = tournament.id?.value
        router.push(
          id ? `/tournaments/${encodeURIComponent(id)}` : "/tournaments"
        )
      },
      onError: (error) => {
        toast.add({
          type: "error",
          title: t("error"),
          description: describeError(error, { 400: t("errors.invalid") }),
        })
      },
    })
  }

  return (
    <Card>
      <CardContent>
        <form id="create-tournament-form" onSubmit={onSubmit} noValidate>
          <FieldGroup>
            <Field data-invalid={fieldErrors.isInvalid("name")}>
              <FieldLabel htmlFor="tournament-name">{t("name")}</FieldLabel>
              <Input
                id="tournament-name"
                value={name}
                onChange={(event) => setName(event.target.value)}
                placeholder={t("namePlaceholder")}
                aria-invalid={fieldErrors.isInvalid("name")}
              />
              <FieldError errors={fieldErrors.messagesFor("name")} />
            </Field>
            <Field data-invalid={fieldErrors.isInvalid("game")}>
              <FieldLabel htmlFor="tournament-game">{t("game")}</FieldLabel>
              <NativeSelect
                id="tournament-game"
                value={gameId}
                onChange={(event) => onGameChange(event.target.value)}
                aria-invalid={fieldErrors.isInvalid("game")}
              >
                <NativeSelectOption value="" disabled>
                  {t("gamePlaceholder")}
                </NativeSelectOption>
                {games.map((option) =>
                  option.id?.value ? (
                    <NativeSelectOption
                      key={option.id.value}
                      value={option.id.value}
                    >
                      {option.title}
                    </NativeSelectOption>
                  ) : null
                )}
              </NativeSelect>
              <FieldError errors={fieldErrors.messagesFor("game")} />
            </Field>
            <Field data-invalid={fieldErrors.isInvalid("teamSize")}>
              <FieldLabel htmlFor="tournament-format">{t("format")}</FieldLabel>
              <NativeSelect
                id="tournament-format"
                value={String(teamSize)}
                onChange={(event) => setTeamSize(Number(event.target.value))}
              >
                {formats.map((size) => (
                  <NativeSelectOption key={size} value={String(size)}>
                    {formatLabel(size)}
                  </NativeSelectOption>
                ))}
              </NativeSelect>
              <FieldDescription>
                {teamSize === 1 ? t("formatPlayers") : t("formatTeams")}
              </FieldDescription>
            </Field>
            <Field data-invalid={fieldErrors.isInvalid("capacity")}>
              <FieldLabel htmlFor="tournament-capacity">
                {t("capacity")}
              </FieldLabel>
              <Input
                id="tournament-capacity"
                type="number"
                inputMode="numeric"
                min={2}
                max={128}
                value={capacity}
                onChange={(event) => setCapacity(event.target.value)}
                className="w-32"
                aria-invalid={fieldErrors.isInvalid("capacity")}
              />
              <FieldDescription>{t("capacityDescription")}</FieldDescription>
              <FieldError errors={fieldErrors.messagesFor("capacity")} />
            </Field>
            <Field data-invalid={fieldErrors.isInvalid("startsAt")}>
              <FieldLabel htmlFor="tournament-starts-at">
                {t("startsAt")}
              </FieldLabel>
              <Input
                id="tournament-starts-at"
                type="datetime-local"
                value={startsAt}
                onChange={(event) => setStartsAt(event.target.value)}
                className="w-64"
                aria-invalid={fieldErrors.isInvalid("startsAt")}
              />
              <FieldError errors={fieldErrors.messagesFor("startsAt")} />
            </Field>
          </FieldGroup>
        </form>
      </CardContent>
      <CardFooter>
        <Button
          type="submit"
          form="create-tournament-form"
          disabled={createTournament.isPending}
        >
          {createTournament.isPending && <Spinner data-icon="inline-start" />}
          {t("submit")}
        </Button>
      </CardFooter>
    </Card>
  )
}
