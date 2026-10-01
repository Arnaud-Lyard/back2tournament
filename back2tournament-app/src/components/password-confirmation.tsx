"use client"

import { Trash2Icon } from "lucide-react"
import { useTranslations } from "next-intl"
import { useId, useState, type FormEvent } from "react"
import { Button } from "@/components/ui/button"
import { Field, FieldLabel } from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import { Spinner } from "@/components/ui/spinner"

interface PasswordConfirmationProps {
  trigger: string
  confirm: string
  pending: boolean
  onConfirm: (password: string) => void
}

export function PasswordConfirmation({
  trigger,
  confirm,
  pending,
  onConfirm,
}: PasswordConfirmationProps) {
  const t = useTranslations("passwordConfirmation")
  const tCommon = useTranslations("common")
  const id = useId()
  const [open, setOpen] = useState(false)
  const [password, setPassword] = useState("")

  if (!open) {
    return (
      <Button
        variant="destructive"
        className="self-start"
        onClick={() => setOpen(true)}
      >
        <Trash2Icon data-icon="inline-start" />
        {trigger}
      </Button>
    )
  }

  function submit(event: FormEvent) {
    event.preventDefault()
    if (password) onConfirm(password)
  }

  function cancel() {
    setPassword("")
    setOpen(false)
  }

  return (
    <form onSubmit={submit} className="flex max-w-sm flex-col gap-3">
      <Field>
        <FieldLabel htmlFor={id}>{t("label")}</FieldLabel>
        <Input
          id={id}
          type="password"
          autoComplete="current-password"
          value={password}
          onChange={(event) => setPassword(event.target.value)}
        />
      </Field>
      <div className="flex flex-wrap gap-2">
        <Button
          type="submit"
          variant="destructive"
          disabled={pending || !password}
        >
          {pending && <Spinner data-icon="inline-start" />}
          {confirm}
        </Button>
        <Button
          type="button"
          variant="ghost"
          onClick={cancel}
          disabled={pending}
        >
          {tCommon("cancel")}
        </Button>
      </div>
    </form>
  )
}
