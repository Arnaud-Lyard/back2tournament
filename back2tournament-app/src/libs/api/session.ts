import "server-only"

import { cookies } from "next/headers"
import { AUTH_COOKIE_NAME } from "@/features/auth/lib/cookie"

export function bearer(token: string | undefined): RequestInit {
  return token ? { headers: { Authorization: `Bearer ${token}` } } : {}
}

export async function withSession(): Promise<RequestInit> {
  return bearer((await cookies()).get(AUTH_COOKIE_NAME)?.value)
}
