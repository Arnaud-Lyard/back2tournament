"use client"

import { MailCheckIcon } from "lucide-react"
import { useTranslations } from "next-intl"
import Link from "next/link"
import { useState, type FormEvent } from "react"
import { Alert, AlertDescription } from "@/components/ui/alert"
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
  Empty,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from "@/components/ui/empty"
import {
  Field,
  FieldDescription,
  FieldError,
  FieldGroup,
  FieldLabel,
} from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import { Spinner } from "@/components/ui/spinner"
import { useAuth } from "@/features/auth/hooks/use-auth"
import { registerSchema } from "@/features/auth/schemas/register.schema"
import { useApiErrorMessage } from "@/hooks/use-api-error-message"
import { useFieldErrors } from "@/hooks/use-field-errors"
import { ApiError } from "@/libs/api/errors"

type RegisterField = "email" | "username" | "password" | "passwordConfirmation"

export default function RegisterPage() {
  const t = useTranslations("auth")
  const describeError = useApiErrorMessage()
  const { signUp } = useAuth()
  const [email, setEmail] = useState("")
  const [username, setUsername] = useState("")
  const [password, setPassword] = useState("")
  const [passwordConfirmation, setPasswordConfirmation] = useState("")
  const [taken, setTaken] = useState<{ email?: string; username?: string }>({})
  const [error, setError] = useState<string | null>(null)
  const [submitted, setSubmitted] = useState(false)
  const [isSubmitting, setIsSubmitting] = useState(false)
  const parsed = registerSchema.safeParse({
    email,
    username,
    password,
    passwordConfirmation,
  })
  const fieldErrors = useFieldErrors<RegisterField>(parsed)

  const takenMessage = {
    email: taken.email === email ? t("errors.emailTaken") : undefined,
    username:
      taken.username === username ? t("errors.usernameTaken") : undefined,
  }

  function errorsFor(field: RegisterField) {
    const conflict =
      field === "email" || field === "username"
        ? takenMessage[field]
        : undefined
    return conflict ? [{ message: conflict }] : fieldErrors.messagesFor(field)
  }

  function isInvalid(field: RegisterField) {
    return Boolean(errorsFor(field)?.length)
  }

  async function onSubmit(event: FormEvent) {
    event.preventDefault()
    setError(null)

    if (!parsed.success) {
      fieldErrors.reveal()
      return
    }

    setIsSubmitting(true)
    try {
      await signUp(parsed.data)
      setSubmitted(true)
    } catch (err) {
      if (err instanceof ApiError && err.code === "emailTaken") {
        setTaken((previous) => ({ ...previous, email }))
      } else if (err instanceof ApiError && err.code === "usernameTaken") {
        setTaken((previous) => ({ ...previous, username }))
      } else {
        setError(describeError(err))
      }
    } finally {
      setIsSubmitting(false)
    }
  }

  if (submitted) {
    return (
      <Empty className="max-w-sm border">
        <EmptyHeader>
          <EmptyMedia variant="icon">
            <MailCheckIcon />
          </EmptyMedia>
          <EmptyTitle>{t("verifyEmailSentTitle")}</EmptyTitle>
          <EmptyDescription>{t("verifyEmailSent")}</EmptyDescription>
        </EmptyHeader>
      </Empty>
    )
  }

  return (
    <Card className="w-full max-w-sm">
      <CardHeader>
        <CardTitle>
          <h1>{t("registerTitle")}</h1>
        </CardTitle>
        <CardDescription>{t("registerDescription")}</CardDescription>
      </CardHeader>
      <CardContent>
        <form id="register-form" onSubmit={onSubmit} noValidate>
          <FieldGroup>
            {error && (
              <Alert variant="destructive">
                <AlertDescription>{error}</AlertDescription>
              </Alert>
            )}
            <Field data-invalid={isInvalid("email")}>
              <FieldLabel htmlFor="email">{t("emailLabel")}</FieldLabel>
              <Input
                id="email"
                name="email"
                type="email"
                value={email}
                onChange={(event) => setEmail(event.target.value)}
                autoComplete="email"
                aria-invalid={isInvalid("email")}
              />
              <FieldError errors={errorsFor("email")} />
            </Field>
            <Field data-invalid={isInvalid("username")}>
              <FieldLabel htmlFor="username">{t("usernameLabel")}</FieldLabel>
              <Input
                id="username"
                name="username"
                value={username}
                onChange={(event) => setUsername(event.target.value)}
                autoComplete="username"
                aria-invalid={isInvalid("username")}
              />
              <FieldError errors={errorsFor("username")} />
            </Field>
            <Field data-invalid={isInvalid("password")}>
              <FieldLabel htmlFor="password">{t("passwordLabel")}</FieldLabel>
              <Input
                id="password"
                name="password"
                type="password"
                value={password}
                onChange={(event) => setPassword(event.target.value)}
                autoComplete="new-password"
                aria-invalid={isInvalid("password")}
              />
              <FieldDescription>{t("passwordHint")}</FieldDescription>
              <FieldError errors={errorsFor("password")} />
            </Field>
            <Field data-invalid={isInvalid("passwordConfirmation")}>
              <FieldLabel htmlFor="passwordConfirmation">
                {t("confirmPasswordLabel")}
              </FieldLabel>
              <Input
                id="passwordConfirmation"
                name="passwordConfirmation"
                type="password"
                value={passwordConfirmation}
                onChange={(event) =>
                  setPasswordConfirmation(event.target.value)
                }
                autoComplete="new-password"
                aria-invalid={isInvalid("passwordConfirmation")}
              />
              <FieldError errors={errorsFor("passwordConfirmation")} />
            </Field>
          </FieldGroup>
        </form>
      </CardContent>
      <CardFooter className="flex-col gap-3">
        <Button
          type="submit"
          form="register-form"
          className="w-full"
          disabled={isSubmitting}
        >
          {isSubmitting && <Spinner data-icon="inline-start" />}
          {t("submitRegister")}
        </Button>
        <p className="text-sm text-muted-foreground">
          {t("haveAccount")}{" "}
          <Link
            href="/login"
            className="font-medium text-foreground underline-offset-4 hover:underline"
          >
            {t("submitLogin")}
          </Link>
        </p>
      </CardFooter>
    </Card>
  )
}
