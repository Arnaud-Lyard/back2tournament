import { cookies } from "next/headers"
import { postRegister } from "@/libs/api/generated/authentication"
import { PostRegisterBodyLocale } from "@/libs/api/generated/endpoints.schemas"
import {
  invalidInput,
  parseRequestBody,
  relayApiResult,
} from "@/libs/api/route-response"
import { registerSchema } from "@/features/auth/schemas/register.schema"
import { LOCALE_COOKIE_NAME, resolveLocale } from "@/features/i18n/routing"

const MAIL_LOCALES: readonly string[] = Object.values(PostRegisterBodyLocale)

function isMailLocale(locale: string): locale is PostRegisterBodyLocale {
  return MAIL_LOCALES.includes(locale)
}

export async function POST(request: Request) {
  const parsed = await parseRequestBody(request, registerSchema)
  if (!parsed.success) return invalidInput(parsed.error)

  const locale = resolveLocale((await cookies()).get(LOCALE_COOKIE_NAME)?.value)

  return relayApiResult(
    postRegister({
      ...parsed.data,
      locale: isMailLocale(locale) ? locale : PostRegisterBodyLocale.fr,
    })
  )
}
