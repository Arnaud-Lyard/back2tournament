import "server-only"

import { cache } from "react"
import { cookies } from "next/headers"
import { createApiClient } from "@/libs/api/client"
import { loadApiResult } from "@/libs/api/load"
import { AUTH_COOKIE_NAME } from "../lib/cookie"
import { decodeSymfonyJwt, isJwtExpired } from "../lib/jwt"
import { authUserFromJwt, toAuthUser } from "../lib/to-auth-user"
import type { AuthUser } from "../types"

export const getCurrentUser = cache(async (): Promise<AuthUser | null> => {
  const token = (await cookies()).get(AUTH_COOKIE_NAME)?.value
  if (!token) return null

  if (isJwtExpired(token)) return null

  const payload = decodeSymfonyJwt(token)
  if (!payload?.username) return null

  const me = await loadApiResult(createApiClient(token).GET("/api/user/me"))
  if (me.ok) return toAuthUser(me.data)

  if (me.status === 401) return null

  return authUserFromJwt(payload.username, payload.roles ?? [])
})
