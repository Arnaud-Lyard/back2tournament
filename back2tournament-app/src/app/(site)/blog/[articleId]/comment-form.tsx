"use client"

import { SendIcon } from "lucide-react"
import Link from "next/link"
import { useRouter } from "next/navigation"
import { useTranslations } from "next-intl"
import { useState, type FormEvent } from "react"
import { Button, buttonVariants } from "@/components/ui/button"
import { Field, FieldError, FieldLabel } from "@/components/ui/field"
import { Spinner } from "@/components/ui/spinner"
import { Textarea } from "@/components/ui/textarea"
import { toast } from "@/components/ui/toast"
import { useAuth } from "@/features/auth/hooks/use-auth"
import { useCreateComment } from "@/features/blog/hooks/use-create-comment"
import { createCommentSchema } from "@/features/blog/schemas/create-comment.schema"
import { useApiErrorMessage } from "@/hooks/use-api-error-message"
import { useFieldErrors } from "@/hooks/use-field-errors"
import { cn } from "@/libs/utils"

export function CommentForm({ articleId }: { articleId: string }) {
  const t = useTranslations("blog.comments")
  const router = useRouter()
  const { isAuthenticated } = useAuth()
  const describeError = useApiErrorMessage()
  const createComment = useCreateComment()
  const [comment, setComment] = useState("")
  const parsed = createCommentSchema.safeParse({ articleId, message: comment })
  const fieldErrors = useFieldErrors<"message">(parsed)

  if (!isAuthenticated) {
    return (
      <div className="flex flex-wrap items-center gap-3 rounded-lg border border-dashed p-4">
        <p className="text-sm text-muted-foreground">{t("signInPrompt")}</p>
        <Link
          href="/login"
          className={cn(buttonVariants({ variant: "outline", size: "sm" }))}
        >
          {t("signIn")}
        </Link>
      </div>
    )
  }

  function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (!parsed.success) {
      fieldErrors.reveal()
      return
    }

    createComment.mutate(parsed.data, {
      onSuccess: () => {
        toast.add({ type: "success", title: t("posted") })
        setComment("")
        fieldErrors.hide()
        router.refresh()
      },
      onError: (error) => {
        toast.add({
          type: "error",
          title: t("postError"),
          description: describeError(error, {
            401: t("errors.signedOut"),
            404: t("errors.gone"),
          }),
        })
      },
    })
  }

  return (
    <form onSubmit={onSubmit} noValidate className="flex flex-col gap-3">
      <Field data-invalid={fieldErrors.isInvalid("message")}>
        <FieldLabel htmlFor="comment-message">{t("label")}</FieldLabel>
        <Textarea
          id="comment-message"
          value={comment}
          onChange={(event) => setComment(event.target.value)}
          placeholder={t("placeholder")}
          rows={4}
          aria-invalid={fieldErrors.isInvalid("message")}
        />
        <FieldError errors={fieldErrors.messagesFor("message")} />
      </Field>
      <div className="flex justify-end">
        <Button type="submit" disabled={createComment.isPending}>
          {createComment.isPending ? (
            <Spinner data-icon="inline-start" />
          ) : (
            <SendIcon data-icon="inline-start" />
          )}
          {t("submit")}
        </Button>
      </div>
    </form>
  )
}
