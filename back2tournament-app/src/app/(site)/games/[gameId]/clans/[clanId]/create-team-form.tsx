"use client"

import { PlusIcon } from "lucide-react"
import { useRouter } from "next/navigation"
import { useTranslations } from "next-intl"
import { useState, type FormEvent } from "react"
import { Button } from "@/components/ui/button"
import {
  Card,
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
import { formatLabel } from "@/features/fights/lib/challenge"
import { useCreateTeam } from "@/features/teams/hooks/use-create-team"
import { createTeamSchema } from "@/features/teams/schemas/create-team.schema"
import { useApiErrorMessage } from "@/hooks/use-api-error-message"
import { useFieldErrors } from "@/hooks/use-field-errors"

interface CreateTeamFormProps {
  clanId: string
  /** The formats above 1v1 the game is played in. */
  formats: number[]
  /** The active members of the clan, leader first. */
  members: { id: string; battletag: string }[]
  leaderId: string
}

export function CreateTeamForm({
  clanId,
  formats,
  members,
  leaderId,
}: CreateTeamFormProps) {
  const t = useTranslations("clans.teams")
  const router = useRouter()
  const describeError = useApiErrorMessage()
  const createTeam = useCreateTeam()
  const [name, setName] = useState("")
  const [size, setSize] = useState(formats[0] ?? 2)
  const [players, setPlayers] = useState<string[]>([leaderId])
  const [leader, setLeader] = useState(leaderId)
  const parsed = createTeamSchema.safeParse({
    clan: clanId,
    name,
    size,
    players,
    leader,
  })
  const fieldErrors = useFieldErrors<"name" | "size" | "players" | "leader">(
    parsed
  )

  function toggle(playerId: string, checked: boolean) {
    setPlayers((current) =>
      checked ? [...current, playerId] : current.filter((id) => id !== playerId)
    )
  }

  function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (!parsed.success) {
      fieldErrors.reveal()
      return
    }

    createTeam.mutate(parsed.data, {
      onSuccess: (team) => {
        toast.add({
          type: "success",
          title: t("created", { name: team.name ?? parsed.data.name }),
        })
        setName("")
        setPlayers([leaderId])
        setLeader(leaderId)
        fieldErrors.hide()
        router.refresh()
      },
      onError: (error) => {
        toast.add({
          type: "error",
          title: t("createError"),
          description: describeError(error, { 403: t("errors.notLeader") }),
        })
      },
    })
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>{t("formTitle")}</CardTitle>
        <CardDescription>{t("formDescription")}</CardDescription>
      </CardHeader>
      <CardContent>
        <form id="create-team-form" onSubmit={onSubmit} noValidate>
          <FieldGroup>
            <Field data-invalid={fieldErrors.isInvalid("name")}>
              <FieldLabel htmlFor="team-name">{t("name")}</FieldLabel>
              <Input
                id="team-name"
                value={name}
                onChange={(event) => setName(event.target.value)}
                placeholder={t("namePlaceholder")}
                aria-invalid={fieldErrors.isInvalid("name")}
              />
              <FieldError errors={fieldErrors.messagesFor("name")} />
            </Field>
            <Field data-invalid={fieldErrors.isInvalid("size")}>
              <FieldLabel htmlFor="team-size">{t("format")}</FieldLabel>
              <NativeSelect
                id="team-size"
                value={String(size)}
                onChange={(event) => setSize(Number(event.target.value))}
              >
                {formats.map((format) => (
                  <NativeSelectOption key={format} value={String(format)}>
                    {formatLabel(format)}
                  </NativeSelectOption>
                ))}
              </NativeSelect>
            </Field>
            <Field data-invalid={fieldErrors.isInvalid("players")}>
              <FieldLabel>{t("lineup", { count: size })}</FieldLabel>
              <ul className="flex flex-col gap-2">
                {members.map((member) => (
                  <li key={member.id}>
                    <label className="flex items-center gap-2 text-sm">
                      <input
                        type="checkbox"
                        className="size-4 accent-primary"
                        checked={players.includes(member.id)}
                        onChange={(event) =>
                          toggle(member.id, event.target.checked)
                        }
                      />
                      {member.battletag}
                    </label>
                  </li>
                ))}
              </ul>
              <FieldDescription>
                {t("selected", { count: players.length, size })}
              </FieldDescription>
              <FieldError errors={fieldErrors.messagesFor("players")} />
            </Field>
            <Field data-invalid={fieldErrors.isInvalid("leader")}>
              <FieldLabel htmlFor="team-leader">{t("captain")}</FieldLabel>
              <NativeSelect
                id="team-leader"
                value={leader}
                onChange={(event) => setLeader(event.target.value)}
                aria-invalid={fieldErrors.isInvalid("leader")}
              >
                {members.map((member) => (
                  <NativeSelectOption key={member.id} value={member.id}>
                    {member.battletag}
                  </NativeSelectOption>
                ))}
              </NativeSelect>
              <FieldDescription>{t("captainDescription")}</FieldDescription>
              <FieldError errors={fieldErrors.messagesFor("leader")} />
            </Field>
          </FieldGroup>
        </form>
      </CardContent>
      <CardFooter>
        <Button
          type="submit"
          form="create-team-form"
          disabled={createTeam.isPending}
        >
          {createTeam.isPending ? (
            <Spinner data-icon="inline-start" />
          ) : (
            <PlusIcon data-icon="inline-start" />
          )}
          {t("submit")}
        </Button>
      </CardFooter>
    </Card>
  )
}
