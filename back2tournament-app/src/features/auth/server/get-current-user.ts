import "server-only"

import { cache } from "react"
import { cookies } from "next/headers"
import { createApiClient } from "@/libs/api/client"
import { loadApiResult } from "@/libs/api/load"
import { AUTH_COOKIE_NAME } from "../lib/cookie"
import { decodeSymfonyJwt, isJwtExpired } from "../lib/jwt"
import { authUserFromJwt, toAuthUser } from "../lib/to-auth-user"
import type { AuthUser } from "../types"

/**
 * The caller, as the backend knows them: asked once per request (cached),
 * so the root layout, pages and footer share one GET /api/users/me.
 */
export const getCurrentUser = cache(async (): Promise<AuthUser | null> => {
  const token = (await cookies()).get(AUTH_COOKIE_NAME)?.value
  if (!token) return null

  if (isJwtExpired(token)) return null

  const payload = decodeSymfonyJwt(token)
  if (!payload?.username) return null

  const me = await loadApiResult(createApiClient(token).GET("/api/users/me"))
  if (me.ok) return toAuthUser(me.data)

  // A revoked or invalid token: the session is over.
  if (me.status === 401) return null

  // The backend could not answer: keep the session the JWT describes.
  return authUserFromJwt(payload.username, payload.roles ?? [])
})
