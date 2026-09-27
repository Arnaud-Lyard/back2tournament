"use client"

import { GavelIcon, Undo2Icon } from "lucide-react"
import { useRouter } from "next/navigation"
import { useTranslations } from "next-intl"
import { useState, type FormEvent } from "react"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Button } from "@/components/ui/button"
import { Field, FieldError, FieldLabel } from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import { Spinner } from "@/components/ui/spinner"
import { toast } from "@/components/ui/toast"
import { useChangeFightStatus } from "@/features/fights/hooks/use-change-fight-status"
import { arbitrationScoresSchema } from "@/features/fights/schemas/change-fight-status.schema"
import { useApiErrorMessage } from "@/hooks/use-api-error-message"
import { useFieldErrors } from "@/hooks/use-field-errors"

interface Side {
  competitor: string
  name: string
  /** What was declared for this side, to start from. */
  score: number
}

interface ArbitrationFormProps {
  fightId: string
  one: Side
  two: Side
  /** A declaration waits for its confirmation: it may be set aside. */
  declared: boolean
  /** A tournament fight cannot end in a draw. */
  tournament: boolean
}

/** Admin only: imposes the scores of a fight in dispute, or sets its declaration aside. */
export function ArbitrationForm({
  fightId,
  one,
  two,
  declared,
  tournament,
}: ArbitrationFormProps) {
  const t = useTranslations("backoffice.fights.arbitration")
  const router = useRouter()
  const describeError = useApiErrorMessage()
  const changeStatus = useChangeFightStatus()
  const [scoreOne, setScoreOne] = useState(declared ? String(one.score) : "")
  const [scoreTwo, setScoreTwo] = useState(declared ? String(two.score) : "")
  const [confirming, setConfirming] = useState(false)
  const parsed = arbitrationScoresSchema.safeParse({
    scoreOne: toNumber(scoreOne),
    scoreTwo: toNumber(scoreTwo),
  })
  const fieldErrors = useFieldErrors<"scoreOne" | "scoreTwo">(parsed)
  const draw =
    tournament &&
    parsed.success &&
    parsed.data.scoreOne === parsed.data.scoreTwo

  function onError(error: unknown) {
    setConfirming(false)
    toast.add({
      type: "error",
      title: t("error"),
      description: describeError(error, {
        400: t("errors.invalid"),
        403: t("errors.forbidden"),
        404: t("errors.notFound"),
        409: t("errors.changed"),
      }),
    })
  }

  function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (!parsed.success || draw) {
      fieldErrors.reveal()
      return
    }
    setConfirming(true)
  }

  function settle() {
    if (!parsed.success) return

    changeStatus.mutate(
      {
        fightId,
        status: "finished",
        scores: {
          [one.competitor]: parsed.data.scoreOne,
          [two.competitor]: parsed.data.scoreTwo,
        },
      },
      {
        onSuccess: () => {
          toast.add({ type: "success", title: t("settled") })
          router.refresh()
        },
        onError,
      }
    )
  }

  function reopen() {
    changeStatus.mutate(
      { fightId, status: "pending" },
      {
        onSuccess: () => {
          toast.add({ type: "success", title: t("reopened") })
          router.refresh()
        },
        onError,
      }
    )
  }

  return (
    <div className="flex flex-col gap-4">
      <div className="flex flex-col gap-1">
        <h3 className="font-medium">{t("title")}</h3>
        <p className="text-sm text-muted-foreground">{t("description")}</p>
      </div>
      <form
        onSubmit={onSubmit}
        noValidate
        className="flex flex-wrap items-start gap-3"
      >
        {(
          [
            ["scoreOne", one, scoreOne, setScoreOne],
            ["scoreTwo", two, scoreTwo, setScoreTwo],
          ] as const
        ).map(([field, side, value, setValue]) => (
          <Field
            key={field}
            data-invalid={fieldErrors.isInvalid(field)}
            className="w-40"
          >
            <FieldLabel htmlFor={`${fightId}-${field}`} className="truncate">
              {t("scoreOf", { name: side.name })}
            </FieldLabel>
            <Input
              id={`${fightId}-${field}`}
              type="number"
              inputMode="numeric"
              min={0}
              value={value}
              onChange={(event) => {
                setValue(event.target.value)
                setConfirming(false)
              }}
              aria-invalid={fieldErrors.isInvalid(field)}
            />
            <FieldError errors={fieldErrors.messagesFor(field)} />
          </Field>
        ))}
        <Button
          type="submit"
          className="mt-6"
          disabled={changeStatus.isPending || confirming}
        >
          <GavelIcon data-icon="inline-start" />
          {t("submit")}
        </Button>
      </form>
      {draw && (
        <p role="alert" className="text-sm text-destructive">
          {t("noDraw")}
        </p>
      )}

      {confirming && parsed.success && (
        <Alert>
          <GavelIcon />
          <AlertTitle>
            {t("confirmTitle", {
              one: one.name,
              two: two.name,
              scoreOne: parsed.data.scoreOne,
              scoreTwo: parsed.data.scoreTwo,
            })}
          </AlertTitle>
          <AlertDescription className="flex flex-col gap-3">
            {t("confirmDescription")}
            <span className="flex flex-wrap gap-2">
              <Button
                size="sm"
                onClick={settle}
                disabled={changeStatus.isPending}
              >
                {changeStatus.isPending ? (
                  <Spinner data-icon="inline-start" />
                ) : (
                  <GavelIcon data-icon="inline-start" />
                )}
                {t("confirm")}
              </Button>
              <Button
                size="sm"
                variant="ghost"
                onClick={() => setConfirming(false)}
                disabled={changeStatus.isPending}
              >
                {t("cancel")}
              </Button>
            </span>
          </AlertDescription>
        </Alert>
      )}

      {declared && (
        <div className="flex flex-wrap items-center gap-3">
          <Button
            variant="outline"
            onClick={reopen}
            disabled={changeStatus.isPending}
          >
            <Undo2Icon data-icon="inline-start" />
            {t("reopen")}
          </Button>
          <p className="text-xs text-muted-foreground">
            {t("reopenDescription")}
          </p>
        </div>
      )}
    </div>
  )
}

/** A number input's text as a number, or undefined while it holds none. */
function toNumber(value: string): number | undefined {
  return value.trim() === "" ? undefined : Number(value)
}
