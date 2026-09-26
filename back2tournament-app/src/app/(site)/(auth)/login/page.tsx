"use client"

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
import { Field, FieldGroup, FieldLabel } from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import { Spinner } from "@/components/ui/spinner"
import { useAuth } from "@/features/auth/hooks/use-auth"
import { loginSchema } from "@/features/auth/schemas/login.schema"
import { useApiErrorMessage } from "@/hooks/use-api-error-message"

export default function LoginPage() {
  const t = useTranslations("auth")
  const describeError = useApiErrorMessage()
  const { signIn } = useAuth()
  const [username, setUsername] = useState("")
  const [password, setPassword] = useState("")
  const [error, setError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)

  async function onSubmit(event: FormEvent) {
    event.preventDefault()
    setError(null)

    const parsed = loginSchema.safeParse({ username, password })
    if (!parsed.success) {
      setError(t("invalidCredentials"))
      return
    }

    setIsSubmitting(true)
    try {
      await signIn(parsed.data)
    } catch (err) {
      setError(
        describeError(err, {
          emailNotVerified: t("errors.emailNotVerified"),
          401: t("invalidCredentials"),
        })
      )
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <Card className="w-full max-w-sm">
      <CardHeader>
        <CardTitle>
          <h1>{t("loginTitle")}</h1>
        </CardTitle>
        <CardDescription>{t("loginDescription")}</CardDescription>
      </CardHeader>
      <CardContent>
        <form id="login-form" onSubmit={onSubmit}>
          <FieldGroup>
            {error && (
              <Alert variant="destructive">
                <AlertDescription>{error}</AlertDescription>
              </Alert>
            )}
            <Field>
              <FieldLabel htmlFor="username">{t("usernameLabel")}</FieldLabel>
              <Input
                id="username"
                name="username"
                value={username}
                onChange={(event) => setUsername(event.target.value)}
                autoComplete="username"
              />
            </Field>
            <Field>
              <FieldLabel htmlFor="password">{t("passwordLabel")}</FieldLabel>
              <Input
                id="password"
                name="password"
                type="password"
                value={password}
                onChange={(event) => setPassword(event.target.value)}
                autoComplete="current-password"
              />
            </Field>
          </FieldGroup>
        </form>
      </CardContent>
      <CardFooter className="flex-col gap-3">
        <Button
          type="submit"
          form="login-form"
          className="w-full"
          disabled={isSubmitting}
        >
          {isSubmitting && <Spinner data-icon="inline-start" />}
          {t("submitLogin")}
        </Button>
        <p className="text-sm text-muted-foreground">
          {t("noAccount")}{" "}
          <Link
            href="/register"
            className="font-medium text-foreground underline-offset-4 hover:underline"
          >
            {t("submitRegister")}
          </Link>
        </p>
      </CardFooter>
    </Card>
  )
}
