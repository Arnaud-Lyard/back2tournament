"use server"

import { cookies } from "next/headers"
import { LOCALE_COOKIE_NAME, isValidLocale } from "./routing"

const ONE_YEAR_IN_SECONDS = 60 * 60 * 24 * 365

export async function setLocale(locale: string) {
  if (!isValidLocale(locale)) return

  const cookieStore = await cookies()
  cookieStore.set(LOCALE_COOKIE_NAME, locale, {
    path: "/",
    maxAge: ONE_YEAR_IN_SECONDS,
    sameSite: "lax",
  })
}
