import { cookies } from "next/headers"
import { createApiClient } from "@/libs/api/client"
import type { paths } from "@/libs/api/schema"
import {
  invalidInput,
  parseRequestBody,
  relayApiResult,
} from "@/libs/api/route-response"
import { registerSchema } from "@/features/auth/schemas/register.schema"
import { LOCALE_COOKIE_NAME, resolveLocale } from "@/features/i18n/routing"

type RegisterBody = NonNullable<
  paths["/api/register"]["post"]["requestBody"]
>["content"]["application/json"]
type MailLocale = NonNullable<RegisterBody["locale"]>

const MAIL_LOCALES: readonly string[] = ["fr", "en"] satisfies MailLocale[]

function isMailLocale(locale: string): locale is MailLocale {
  return MAIL_LOCALES.includes(locale)
}

export async function POST(request: Request) {
  const parsed = await parseRequestBody(request, registerSchema)
  if (!parsed.success) return invalidInput(parsed.error)

  const locale = resolveLocale((await cookies()).get(LOCALE_COOKIE_NAME)?.value)

  return relayApiResult(
    createApiClient().POST("/api/register", {
      body: {
        ...parsed.data,
        locale: isMailLocale(locale) ? locale : "fr",
      },
    })
  )
}
