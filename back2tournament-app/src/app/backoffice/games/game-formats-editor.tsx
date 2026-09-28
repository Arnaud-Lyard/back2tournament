"use client"

import { CheckIcon, PencilIcon, XIcon } from "lucide-react"
import { useRouter } from "next/navigation"
import { useTranslations } from "next-intl"
import { useState, type FormEvent } from "react"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Spinner } from "@/components/ui/spinner"
import { toast } from "@/components/ui/toast"
import { formatLabel } from "@/features/fights/lib/challenge"
import { useUpdateGame } from "@/features/games/hooks/use-update-game"
import { parseTeamSizes } from "@/features/games/lib/team-sizes"
import { updateGameSchema } from "@/features/games/schemas/update-game.schema"
import { useApiErrorMessage } from "@/hooks/use-api-error-message"

interface GameFormatsEditorProps {
  gameId: string
  title: string
  teamSizes: number[]
}

export function GameFormatsEditor({
  gameId,
  title,
  teamSizes,
}: GameFormatsEditorProps) {
  const t = useTranslations("backoffice.games")
  const tValidation = useTranslations("validation")
  const router = useRouter()
  const describeError = useApiErrorMessage()
  const updateGame = useUpdateGame()
  const [editing, setEditing] = useState(false)
  const [text, setText] = useState(teamSizes.join(", "))
  const parsed = updateGameSchema.safeParse({ teamSizes: parseTeamSizes(text) })

  function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (!parsed.success) return

    updateGame.mutate(
      { id: gameId, ...parsed.data },
      {
        onSuccess: () => {
          toast.add({ type: "success", title: t("formatsSaved", { title }) })
          setEditing(false)
          router.refresh()
        },
        onError: (error) => {
          toast.add({
            type: "error",
            title: t("formatsError"),
            description: describeError(error, { 403: t("errors.forbidden") }),
          })
        },
      }
    )
  }

  if (!editing) {
    return (
      <div className="flex flex-wrap items-center gap-1">
        {teamSizes.map((size) => (
          <Badge key={size} variant="outline">
            {formatLabel(size)}
          </Badge>
        ))}
        <Button
          variant="ghost"
          size="icon-sm"
          onClick={() => setEditing(true)}
          aria-label={t("editFormats", { title })}
        >
          <PencilIcon />
        </Button>
      </div>
    )
  }

  return (
    <form onSubmit={onSubmit} noValidate className="flex items-center gap-1">
      <Input
        value={text}
        onChange={(event) => setText(event.target.value)}
        aria-label={t("formats")}
        aria-invalid={!parsed.success}
        className="h-8 w-32"
        title={parsed.success ? undefined : tValidation("invalidTeamSizes")}
      />
      <Button
        type="submit"
        size="icon-sm"
        disabled={!parsed.success || updateGame.isPending}
        aria-label={t("saveFormats")}
      >
        {updateGame.isPending ? <Spinner /> : <CheckIcon />}
      </Button>
      <Button
        type="button"
        variant="ghost"
        size="icon-sm"
        onClick={() => {
          setText(teamSizes.join(", "))
          setEditing(false)
        }}
        aria-label={t("cancelFormats")}
      >
        <XIcon />
      </Button>
    </form>
  )
}
