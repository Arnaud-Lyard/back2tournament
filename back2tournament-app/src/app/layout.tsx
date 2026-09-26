import { Geist_Mono, IBM_Plex_Sans, Oxanium } from "next/font/google"
import { NextIntlClientProvider } from "next-intl"
import { getLocale, getMessages } from "next-intl/server"
import type { Metadata } from "next"

import "./globals.css"
import { cn } from "@/libs/utils"
import { getCurrentUser } from "@/features/auth/server/get-current-user"
import { baseUrl, siteConfig } from "@/features/site/config"
import Providers from "./providers"

const oxaniumHeading = Oxanium({
  subsets: ["latin"],
  variable: "--font-heading",
})

const ibmPlexSans = IBM_Plex_Sans({
  subsets: ["latin"],
  variable: "--font-sans",
})

const fontMono = Geist_Mono({
  subsets: ["latin"],
  variable: "--font-mono",
})

export const metadata: Metadata = {
  metadataBase: new URL(baseUrl),
  title: {
    default: siteConfig.title,
    template: `%s | ${siteConfig.appName}`,
  },
  description: siteConfig.description,
  manifest: "/manifest.webmanifest",
  icons: {
    icon: siteConfig.icons.favicon,
    apple: siteConfig.icons.appleTouchIcon,
  },
  openGraph: {
    title: siteConfig.title,
    description: siteConfig.description,
    url: siteConfig.url,
    siteName: siteConfig.appName,
    images: [
      {
        url: siteConfig.images.og,
        width: siteConfig.images.ogWidth,
        height: siteConfig.images.ogHeight,
      },
    ],
  },
}

export default async function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode
}>) {
  const [locale, messages, user] = await Promise.all([
    getLocale(),
    getMessages(),
    getCurrentUser(),
  ])

  return (
    <html
      lang={locale}
      suppressHydrationWarning
      className={cn(
        "antialiased",
        fontMono.variable,
        "font-sans",
        ibmPlexSans.variable,
        oxaniumHeading.variable
      )}
    >
      <body>
        <NextIntlClientProvider messages={messages}>
          <Providers initialUser={user}>{children}</Providers>
        </NextIntlClientProvider>
      </body>
    </html>
  )
}
