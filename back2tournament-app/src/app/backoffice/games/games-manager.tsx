"use client"

import { PlusIcon } from "lucide-react"
import { useRouter } from "next/navigation"
import { useFormatter, useTranslations } from "next-intl"
import { useState, type FormEvent } from "react"
import { CopyButton } from "@/components/copy-button"
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
import { Spinner } from "@/components/ui/spinner"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { toast } from "@/components/ui/toast"
import { useCreateGame } from "@/features/games/hooks/use-create-game"
import { parseTeamSizes } from "@/features/games/lib/team-sizes"
import { createGameSchema } from "@/features/games/schemas/create-game.schema"
import type { Game } from "@/features/games/types"
import { useApiErrorMessage } from "@/hooks/use-api-error-message"
import { useFieldErrors } from "@/hooks/use-field-errors"
import { ListCard } from "../list-card"
import { GameFormatsEditor } from "./game-formats-editor"
import { LoadError } from "../load-error"

/** `games` is null when the list could not be loaded. */
export function GamesManager({ games }: { games: Game[] | null }) {
  const t = useTranslations("backoffice.games")
  const format = useFormatter()
  const router = useRouter()
  const describeError = useApiErrorMessage()
  const createGame = useCreateGame()
  const [title, setTitle] = useState("")
  const [formats, setFormats] = useState("1")
  const parsed = createGameSchema.safeParse({
    title,
    teamSizes: parseTeamSizes(formats),
  })
  const fieldErrors = useFieldErrors<"title" | "teamSizes">(parsed)

  function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (!parsed.success) {
      fieldErrors.reveal()
      return
    }

    createGame.mutate(parsed.data, {
      onSuccess: (game) => {
        toast.add({
          type: "success",
          title: t("success"),
          description: t("successDescription", {
            title: game.title ?? parsed.data.title,
          }),
        })
        setTitle("")
        setFormats("1")
        router.refresh()
        fieldErrors.hide()
      },
      onError: (error) => {
        toast.add({
          type: "error",
          title: t("error"),
          description: describeError(error, { 403: t("errors.forbidden") }),
        })
      },
    })
  }

  return (
    <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)]">
      <Card>
        <CardHeader>
          <CardTitle>{t("formTitle")}</CardTitle>
          <CardDescription>{t("formDescription")}</CardDescription>
        </CardHeader>
        <CardContent>
          <form id="create-game-form" onSubmit={onSubmit} noValidate>
            <FieldGroup>
              <Field data-invalid={fieldErrors.isInvalid("title")}>
                <FieldLabel htmlFor="game-title">{t("name")}</FieldLabel>
                <Input
                  id="game-title"
                  value={title}
                  onChange={(event) => setTitle(event.target.value)}
                  placeholder={t("namePlaceholder")}
                  aria-invalid={fieldErrors.isInvalid("title")}
                />
                <FieldError errors={fieldErrors.messagesFor("title")} />
              </Field>
              <Field data-invalid={fieldErrors.isInvalid("teamSizes")}>
                <FieldLabel htmlFor="game-formats">{t("formats")}</FieldLabel>
                <Input
                  id="game-formats"
                  value={formats}
                  onChange={(event) => setFormats(event.target.value)}
                  placeholder="1, 2, 3"
                  aria-invalid={fieldErrors.isInvalid("teamSizes")}
                />
                <FieldDescription>{t("formatsDescription")}</FieldDescription>
                <FieldError errors={fieldErrors.messagesFor("teamSizes")} />
              </Field>
            </FieldGroup>
          </form>
        </CardContent>
        <CardFooter>
          <Button
            type="submit"
            form="create-game-form"
            disabled={createGame.isPending}
          >
            {createGame.isPending ? (
              <Spinner data-icon="inline-start" />
            ) : (
              <PlusIcon data-icon="inline-start" />
            )}
            {t("submit")}
          </Button>
        </CardFooter>
      </Card>
      {games === null ? (
        <LoadError />
      ) : (
        <ListCard
          title={t("list.title")}
          description={t("list.description")}
          count={games.length}
          emptyTitle={t("list.emptyTitle")}
          emptyDescription={t("list.emptyDescription")}
        >
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>{t("columns.name")}</TableHead>
                <TableHead>{t("columns.formats")}</TableHead>
                <TableHead>{t("columns.id")}</TableHead>
                <TableHead>{t("columns.createdAt")}</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {games.map((game) => {
                const id = game.id?.value
                if (!id) return null

                return (
                  <TableRow key={id}>
                    <TableCell className="font-medium">{game.title}</TableCell>
                    <TableCell>
                      <GameFormatsEditor
                        gameId={id}
                        title={game.title ?? ""}
                        teamSizes={game.teamSizes ?? [1]}
                      />
                    </TableCell>
                    <TableCell>
                      <div className="flex items-center gap-1">
                        <code className="font-mono text-xs">
                          {id.slice(0, 8)}
                        </code>
                        <CopyButton value={id} label={t("copyId")} />
                      </div>
                    </TableCell>
                    <TableCell>
                      {game.createdAt
                        ? format.dateTime(new Date(game.createdAt), {
                            dateStyle: "medium",
                          })
                        : null}
                    </TableCell>
                  </TableRow>
                )
              })}
            </TableBody>
          </Table>
        </ListCard>
      )}
    </div>
  )
}
