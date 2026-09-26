import type { NextConfig } from "next"
import { withSentryConfig } from "@sentry/nextjs/config"
import createNextIntlPlugin from "next-intl/plugin"

const withNextIntl = createNextIntlPlugin("./src/features/i18n/request.ts")

const nextConfig: NextConfig = {
  output: "standalone",
  reactStrictMode: true,
  poweredByHeader: false,
  // The Playwright e2e suite drives the dev server via 127.0.0.1.
  allowedDevOrigins: ["127.0.0.1"],
}

export default withSentryConfig(withNextIntl(nextConfig), {
  org: process.env.SENTRY_ORG,
  project: process.env.SENTRY_PROJECT,
  silent: true,
})
