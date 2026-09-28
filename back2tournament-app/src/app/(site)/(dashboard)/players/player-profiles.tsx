"use client"

import { Gamepad2Icon, PencilIcon, Trash2Icon } from "lucide-react"
import { useRouter } from "next/navigation"
import { useTranslations } from "next-intl"
import { useMemo, useState } from "react"
import { Button } from "@/components/ui/button"
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import {
  Empty,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from "@/components/ui/empty"
import { Field, FieldError } from "@/components/ui/field"
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
import { useAuth } from "@/features/auth/hooks/use-auth"
import { findGame } from "@/features/games/lib/find-game"
import type { Game } from "@/features/games/types"
import { useDeletePlayer } from "@/features/players/hooks/use-delete-player"
import { useUpdatePlayer } from "@/features/players/hooks/use-update-player"
import { updatePlayerSchema } from "@/features/players/schemas/update-player.schema"
import { useApiErrorMessage } from "@/hooks/use-api-error-message"
import { useFieldErrors } from "@/hooks/use-field-errors"

interface Profile {
  playerId: string
  gameId: string
  gameTitle: string
  battletag: string
}

export function PlayerProfiles({ games }: { games: Game[] }) {
  const t = useTranslations("players.profiles")
  const tCommon = useTranslations("common")
  const router = useRouter()
  const { user } = useAuth()
  const describeError = useApiErrorMessage()
  const updatePlayer = useUpdatePlayer()
  const deletePlayer = useDeletePlayer()
  const [editing, setEditing] = useState<string | null>(null)
  const [battletag, setBattletag] = useState("")
  const [confirming, setConfirming] = useState<string | null>(null)
  const parsed = updatePlayerSchema.safeParse({ battletag })
  const fieldErrors = useFieldErrors<"battletag">(parsed)
  const busy = updatePlayer.isPending || deletePlayer.isPending

  const profiles = useMemo(
    () => toProfiles(user?.playersByGame, games),
    [user, games]
  )

  function startEditing(profile: Profile) {
    setConfirming(null)
    setEditing(profile.playerId)
    setBattletag(profile.battletag)
    fieldErrors.hide()
  }

  function stopEditing() {
    setEditing(null)
    fieldErrors.hide()
  }

  function rename(profile: Profile) {
    if (!parsed.success) {
      fieldErrors.reveal()
      return
    }

    updatePlayer.mutate(
      { id: profile.playerId, ...parsed.data },
      {
        onSuccess: (updated) => {
          toast.add({
            type: "success",
            title: t("renamed"),
            description: t("renamedDescription", {
              battletag: updated.battletag ?? parsed.data.battletag,
            }),
          })
          stopEditing()
          router.refresh()
        },
        onError: (error) => {
          const message = describeError(error, {
            403: t("errors.notYours"),
            404: t("errors.gone"),
          })
          toast.add({
            type: "error",
            title: t("renameError"),
            description: message,
          })
        },
      }
    )
  }

  function remove(profile: Profile) {
    deletePlayer.mutate(profile.playerId, {
      onSuccess: () => {
        toast.add({
          type: "success",
          title: t("deleted"),
          description: t("deletedDescription", {
            battletag: profile.battletag,
          }),
        })
        setConfirming(null)
        router.refresh()
      },
      onError: (error) => {
        const message = describeError(error, {
          403: t("errors.notYours"),
          404: t("errors.gone"),
          409: t("errors.fighting"),
        })
        setConfirming(null)
        toast.add({
          type: "error",
          title: t("deleteError"),
          description: message,
        })
      },
    })
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>{t("listTitle")}</CardTitle>
        <CardDescription>{t("listDescription")}</CardDescription>
      </CardHeader>
      <CardContent>
        {profiles.length === 0 ? (
          <Empty className="border">
            <EmptyHeader>
              <EmptyMedia variant="icon">
                <Gamepad2Icon />
              </EmptyMedia>
              <EmptyTitle>{t("emptyTitle")}</EmptyTitle>
              <EmptyDescription>{t("emptyDescription")}</EmptyDescription>
            </EmptyHeader>
          </Empty>
        ) : (
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>{t("columns.game")}</TableHead>
                <TableHead>{t("columns.battletag")}</TableHead>
                <TableHead className="text-right">
                  {t("columns.actions")}
                </TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {profiles.map((profile) => (
                <TableRow key={profile.playerId}>
                  <TableCell className="font-medium">
                    {profile.gameTitle}
                  </TableCell>
                  <TableCell>
                    {editing === profile.playerId ? (
                      <Field data-invalid={fieldErrors.isInvalid("battletag")}>
                        <Input
                          aria-label={t("columns.battletag")}
                          value={battletag}
                          onChange={(event) => setBattletag(event.target.value)}
                          onKeyDown={(event) => {
                            if (event.key === "Enter") rename(profile)
                            if (event.key === "Escape") stopEditing()
                          }}
                          placeholder={t("battletagPlaceholder")}
                          autoComplete="off"
                          aria-invalid={fieldErrors.isInvalid("battletag")}
                        />
                        <FieldError
                          errors={fieldErrors.messagesFor("battletag")}
                        />
                      </Field>
                    ) : (
                      profile.battletag
                    )}
                  </TableCell>
                  <TableCell>
                    <div className="flex flex-wrap items-center justify-end gap-2">
                      {editing === profile.playerId ? (
                        <>
                          <Button
                            onClick={() => rename(profile)}
                            disabled={busy}
                          >
                            {updatePlayer.isPending && (
                              <Spinner data-icon="inline-start" />
                            )}
                            {tCommon("save")}
                          </Button>
                          <Button
                            variant="ghost"
                            onClick={stopEditing}
                            disabled={busy}
                          >
                            {tCommon("cancel")}
                          </Button>
                        </>
                      ) : confirming === profile.playerId ? (
                        <>
                          <span className="text-xs text-muted-foreground">
                            {t("confirmDelete")}
                          </span>
                          <Button
                            variant="destructive"
                            onClick={() => remove(profile)}
                            disabled={busy}
                          >
                            {deletePlayer.isPending && (
                              <Spinner data-icon="inline-start" />
                            )}
                            {tCommon("confirm")}
                          </Button>
                          <Button
                            variant="ghost"
                            onClick={() => setConfirming(null)}
                            disabled={busy}
                          >
                            {tCommon("cancel")}
                          </Button>
                        </>
                      ) : (
                        <>
                          <Button
                            variant="outline"
                            onClick={() => startEditing(profile)}
                            disabled={busy}
                          >
                            <PencilIcon data-icon="inline-start" />
                            {t("rename")}
                          </Button>
                          <Button
                            variant="destructive"
                            onClick={() => setConfirming(profile.playerId)}
                            disabled={busy}
                          >
                            <Trash2Icon data-icon="inline-start" />
                            {tCommon("delete")}
                          </Button>
                        </>
                      )}
                    </div>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        )}
      </CardContent>
    </Card>
  )
}

function toProfiles(
  playersByGame:
    | Record<string, { id: string; battletag: string } | undefined>
    | undefined,
  games: Game[]
): Profile[] {
  return Object.entries(playersByGame ?? {})
    .flatMap(([gameId, profile]) =>
      profile
        ? [
            {
              playerId: profile.id,
              gameId,
              gameTitle: findGame(games, gameId)?.title ?? gameId,
              battletag: profile.battletag,
            },
          ]
        : []
    )
    .sort((one, other) => one.gameTitle.localeCompare(other.gameTitle))
}
