import * as Sentry from "@sentry/nextjs"

export async function register() {
  const dsn = process.env.NEXT_PUBLIC_SENTRY_DSN
  if (!dsn) return

  if (process.env.NEXT_RUNTIME === "nodejs") {
    Sentry.init({
      dsn,
      environment: process.env.NEXT_PUBLIC_APP_ENV,
      tracesSampleRate: 0.1,
      enabled: process.env.NODE_ENV === "production",
    })
  }
}

export const onRequestError = Sentry.captureRequestError
