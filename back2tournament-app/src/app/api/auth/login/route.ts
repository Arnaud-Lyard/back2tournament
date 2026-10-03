import { NextResponse } from "next/server"
import {
  AUTH_COOKIE_NAME,
  AUTH_COOKIE_OPTIONS,
} from "@/features/auth/lib/cookie"
import { decodeSymfonyJwt, getJwtExpirySeconds } from "@/features/auth/lib/jwt"
import { authUserFromJwt } from "@/features/auth/lib/to-auth-user"
import { loginSchema } from "@/features/auth/schemas/login.schema"
import { toApiError } from "@/libs/api/errors"
import { postLogin } from "@/libs/api/generated/authentication"
import { invalidInput, parseRequestBody } from "@/libs/api/route-response"

export async function POST(request: Request) {
  const parsed = await parseRequestBody(request, loginSchema)
  if (!parsed.success) return invalidInput(parsed.error)

  let result
  try {
    result = await postLogin(parsed.data)
  } catch {
    return NextResponse.json(
      { message: "Backend unavailable" },
      { status: 502 }
    )
  }

  if (result.status !== 200) {
    const apiError = toApiError(result.status, result.data)
    return NextResponse.json(
      { message: apiError.message, code: apiError.code },
      { status: apiError.status }
    )
  }

  const token = result.data.token
  const payload = token ? decodeSymfonyJwt(token) : null
  if (!token || !payload?.username) {
    return NextResponse.json(
      { message: "Malformed token from backend" },
      { status: 502 }
    )
  }

  const response = NextResponse.json(
    authUserFromJwt(payload.username, payload.roles ?? [])
  )
  response.cookies.set(AUTH_COOKIE_NAME, token, {
    ...AUTH_COOKIE_OPTIONS,
    maxAge: getJwtExpirySeconds(token),
  })
  return response
}
