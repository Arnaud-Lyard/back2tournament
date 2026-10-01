import { cookies } from "next/headers"
import { getServerApiClient } from "@/libs/api/client"
import {
  invalidInput,
  parseRequestBody,
  relayApiResult,
} from "@/libs/api/route-response"
import { AUTH_COOKIE_NAME } from "@/features/auth/lib/cookie"
import { passwordConfirmationSchema } from "@/features/auth/schemas/password-confirmation.schema"

export async function POST(request: Request) {
  const parsed = await parseRequestBody(request, passwordConfirmationSchema)
  if (!parsed.success) return invalidInput(parsed.error)

  const client = await getServerApiClient()
  const response = await relayApiResult(
    client.POST("/api/user/me/deletion", { body: parsed.data })
  )

  if (response.ok) {
    const cookieStore = await cookies()
    cookieStore.delete(AUTH_COOKIE_NAME)
  }

  return response
}
