import { env } from "@/libs/env"

export const AUTH_COOKIE_NAME = env.AUTH_COOKIE_NAME

export const FALLBACK_COOKIE_MAX_AGE = 60 * 60

export const AUTH_COOKIE_OPTIONS = {
  httpOnly: true,
  secure: env.NEXT_PUBLIC_APP_ENV === "production",
  sameSite: "lax" as const,
  path: "/",
}
