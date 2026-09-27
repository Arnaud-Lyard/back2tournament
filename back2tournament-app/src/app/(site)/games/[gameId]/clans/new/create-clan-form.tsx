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
import { Spinner } from "@/components/ui/spinner"
import { toast } from "@/components/ui/toast"
import { useCreateClan } from "@/features/clans/hooks/use-create-clan"
import { createClanSchema } from "@/features/clans/schemas/create-clan.schema"
import { useApiErrorMessage } from "@/hooks/use-api-error-message"
import { useFieldErrors } from "@/hooks/use-field-errors"

export function CreateClanForm({ gameId }: { gameId: string }) {
  const t = useTranslations("clans.create")
  const router = useRouter()
  const describeError = useApiErrorMessage()
  const createClan = useCreateClan()
  const [name, setName] = useState("")
  const [tag, setTag] = useState("")
  const parsed = createClanSchema.safeParse({ game: gameId, name, tag })
  const fieldErrors = useFieldErrors<"name" | "tag">(parsed)

  function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (!parsed.success) {
      fieldErrors.reveal()
      return
    }

    createClan.mutate(parsed.data, {
      onSuccess: (clan) => {
        toast.add({
          type: "success",
          title: t("success", { name: clan.name ?? "" }),
        })
        const id = clan.id?.value
        router.push(
          `/games/${encodeURIComponent(gameId)}/clans${id ? `/${encodeURIComponent(id)}` : ""}`
        )
        router.refresh()
      },
      onError: (error) => {
        toast.add({
          type: "error",
          title: t("error"),
          description: describeError(error, {
            404: t("errors.noProfile"),
            409: t("errors.conflict"),
          }),
        })
      },
    })
  }

  return (
    <Card>
      <CardContent>
        <form id="create-clan-form" onSubmit={onSubmit} noValidate>
          <FieldGroup>
            <Field data-invalid={fieldErrors.isInvalid("name")}>
              <FieldLabel htmlFor="clan-name">{t("name")}</FieldLabel>
              <Input
                id="clan-name"
                value={name}
                onChange={(event) => setName(event.target.value)}
                placeholder={t("namePlaceholder")}
                aria-invalid={fieldErrors.isInvalid("name")}
              />
              <FieldError errors={fieldErrors.messagesFor("name")} />
            </Field>
            <Field data-invalid={fieldErrors.isInvalid("tag")}>
              <FieldLabel htmlFor="clan-tag">{t("tag")}</FieldLabel>
              <Input
                id="clan-tag"
                value={tag}
                onChange={(event) => setTag(event.target.value.toUpperCase())}
                placeholder="B2T"
                maxLength={5}
                autoComplete="off"
                className="w-32 font-mono uppercase"
                aria-invalid={fieldErrors.isInvalid("tag")}
              />
              <FieldDescription>{t("tagDescription")}</FieldDescription>
              <FieldError errors={fieldErrors.messagesFor("tag")} />
            </Field>
          </FieldGroup>
        </form>
      </CardContent>
      <CardFooter>
        <Button
          type="submit"
          form="create-clan-form"
          disabled={createClan.isPending}
        >
          {createClan.isPending && <Spinner data-icon="inline-start" />}
          {t("submit")}
        </Button>
      </CardFooter>
    </Card>
  )
}
