"use client"

import { CheckIcon, PencilIcon } from "lucide-react"
import { useRouter } from "next/navigation"
import { useTranslations } from "next-intl"
import { useState, type FormEvent } from "react"
import { Button } from "@/components/ui/button"
import { Field, FieldError, FieldLabel } from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import { Spinner } from "@/components/ui/spinner"
import { toast } from "@/components/ui/toast"
import { useConfirmResults } from "@/features/fights/hooks/use-confirm-results"
import { useDeclareResults } from "@/features/fights/hooks/use-declare-results"
import type { ChallengeStage } from "@/features/fights/lib/challenge"
import { declareResultsSchema } from "@/features/fights/schemas/declare-results.schema"
import { useApiErrorMessage } from "@/hooks/use-api-error-message"
import { useFieldErrors } from "@/hooks/use-field-errors"

interface ChallengeActionsProps {
  fightId: string
  stage: ChallengeStage
  /** What is declared so far, to start a correction from. */
  score: number
  opponentScore: number
}

export function ChallengeActions({
  fightId,
  stage,
  score,
  opponentScore,
}: ChallengeActionsProps) {
  const t = useTranslations("challenges")
  const [correcting, setCorrecting] = useState(false)

  if (stage === "confirm") return <ConfirmButton fightId={fightId} />

  if (stage === "awaiting" && !correcting) {
    return (
      <Button variant="outline" size="sm" onClick={() => setCorrecting(true)}>
        <PencilIcon data-icon="inline-start" />
        {t("correct")}
      </Button>
    )
  }

  return (
    <DeclareForm
      fightId={fightId}
      initialScore={stage === "awaiting" ? score : undefined}
      initialOpponentScore={stage === "awaiting" ? opponentScore : undefined}
      onDone={() => setCorrecting(false)}
    />
  )
}

function DeclareForm({
  fightId,
  initialScore,
  initialOpponentScore,
  onDone,
}: {
  fightId: string
  initialScore?: number
  initialOpponentScore?: number
  onDone: () => void
}) {
  const t = useTranslations("challenges")
  const router = useRouter()
  const describeError = useApiErrorMessage()
  const declare = useDeclareResults()
  const [score, setScore] = useState(initialScore?.toString() ?? "")
  const [opponentScore, setOpponentScore] = useState(
    initialOpponentScore?.toString() ?? ""
  )
  const parsed = declareResultsSchema.safeParse({
    score: toNumber(score),
    opponentScore: toNumber(opponentScore),
  })
  const fieldErrors = useFieldErrors<"score" | "opponentScore">(parsed)
  const formId = `declare-${fightId}`

  function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (!parsed.success) {
      fieldErrors.reveal()
      return
    }

    declare.mutate(
      { fightId, ...parsed.data },
      {
        onSuccess: () => {
          toast.add({ type: "success", title: t("declared") })
          onDone()
          router.refresh()
        },
        onError: (error) => {
          toast.add({
            type: "error",
            title: t("declareError"),
            description: describeError(error, {
              400: t("errors.draw"),
              403: t("errors.notYours"),
              409: t("errors.alreadyDeclared"),
            }),
          })
        },
      }
    )
  }

  return (
    <form
      id={formId}
      onSubmit={onSubmit}
      noValidate
      className="flex flex-wrap items-end gap-3"
    >
      <Field data-invalid={fieldErrors.isInvalid("score")} className="w-28">
        <FieldLabel htmlFor={`${formId}-score`}>{t("myScore")}</FieldLabel>
        <Input
          id={`${formId}-score`}
          type="number"
          inputMode="numeric"
          min={0}
          value={score}
          onChange={(event) => setScore(event.target.value)}
          aria-invalid={fieldErrors.isInvalid("score")}
        />
        <FieldError errors={fieldErrors.messagesFor("score")} />
      </Field>
      <Field
        data-invalid={fieldErrors.isInvalid("opponentScore")}
        className="w-28"
      >
        <FieldLabel htmlFor={`${formId}-opponent-score`}>
          {t("opponentScore")}
        </FieldLabel>
        <Input
          id={`${formId}-opponent-score`}
          type="number"
          inputMode="numeric"
          min={0}
          value={opponentScore}
          onChange={(event) => setOpponentScore(event.target.value)}
          aria-invalid={fieldErrors.isInvalid("opponentScore")}
        />
        <FieldError errors={fieldErrors.messagesFor("opponentScore")} />
      </Field>
      <Button type="submit" size="sm" disabled={declare.isPending}>
        {declare.isPending && <Spinner data-icon="inline-start" />}
        {t("declare")}
      </Button>
    </form>
  )
}

function ConfirmButton({ fightId }: { fightId: string }) {
  const t = useTranslations("challenges")
  const router = useRouter()
  const describeError = useApiErrorMessage()
  const confirm = useConfirmResults()

  function onConfirm() {
    confirm.mutate(fightId, {
      onSuccess: () => {
        toast.add({ type: "success", title: t("confirmed") })
        router.refresh()
      },
      onError: (error) => {
        toast.add({
          type: "error",
          title: t("confirmError"),
          description: describeError(error, {
            403: t("errors.cannotConfirm"),
            409: t("errors.notAwaiting"),
          }),
        })
      },
    })
  }

  return (
    <div className="flex flex-wrap items-center gap-3">
      <Button size="sm" onClick={onConfirm} disabled={confirm.isPending}>
        {confirm.isPending ? (
          <Spinner data-icon="inline-start" />
        ) : (
          <CheckIcon data-icon="inline-start" />
        )}
        {t("confirm")}
      </Button>
      <p className="text-xs text-muted-foreground">{t("disagree")}</p>
    </div>
  )
}

/** A number input's text as a number, or undefined while it holds none. */
function toNumber(value: string): number | undefined {
  return value.trim() === "" ? undefined : Number(value)
}
