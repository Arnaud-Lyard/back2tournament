import { env } from "@/libs/env"

export const AUTH_COOKIE_NAME = env.AUTH_COOKIE_NAME

/** Fallback TTL (seconds) used only if the JWT's `exp` claim can't be read. */
export const FALLBACK_COOKIE_MAX_AGE = 60 * 60

export const AUTH_COOKIE_OPTIONS = {
  httpOnly: true,
  secure: env.NEXT_PUBLIC_APP_ENV === "production",
  sameSite: "lax" as const,
  path: "/",
}
