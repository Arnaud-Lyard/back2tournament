import { cookies } from "next/headers"
import { NextResponse } from "next/server"
import { env } from "@/libs/env"
import { AUTH_COOKIE_NAME } from "@/features/auth/lib/cookie"

/**
 * Calls Symfony's /api/logout best-effort while the token is still valid,
 * then unconditionally clears the cookie. Cookie clearing must never be
 * blocked by a slow or failing backend call.
 */
export async function POST() {
  const cookieStore = await cookies()
  const token = cookieStore.get(AUTH_COOKIE_NAME)?.value

  try {
    if (token) {
      await fetch(`${env.SYMFONY_API_URL}/api/logout`, {
        method: "POST",
        headers: { Authorization: `Bearer ${token}` },
        signal: AbortSignal.timeout(3000),
      })
    }
  } catch {
    // Best-effort — the cookie is cleared regardless below.
  } finally {
    cookieStore.delete(AUTH_COOKIE_NAME)
  }

  return NextResponse.json({ message: "logged out" })
}
