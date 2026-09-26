import { z } from "zod"
import { env } from "@/libs/env"
import siteData from "./site.config.json"

const siteConfigSchema = z.object({
  appName: z.string().min(1),
  title: z.string().min(1),
  description: z.string().min(1),
  url: z.string().url(),
  languages: z.object({
    supported: z.array(z.string().min(1)).min(1),
    default: z.string().min(1),
  }),
  theme: z.object({
    light: z.string().min(1),
    dark: z.string().min(1),
  }),
  images: z.object({
    og: z.string().min(1),
    logo: z.string().min(1),
    ogWidth: z.number().positive(),
    ogHeight: z.number().positive(),
  }),
  icons: z.object({
    favicon: z.string().min(1),
    icon: z.string().min(1),
    appleTouchIcon: z.string().min(1),
  }),
  organization: z.object({
    name: z.string().min(1),
    logo: z.string().min(1),
  }),
  social: z.record(z.string().min(1), z.string().min(1)),
})

export type SiteConfig = z.infer<typeof siteConfigSchema>

export const siteConfig: SiteConfig = siteConfigSchema.parse(siteData)

export const baseUrl: string = env.NEXT_PUBLIC_SITE_URL || siteConfig.url

export type Locale = (typeof siteConfig.languages.supported)[number]

export const supportedLocales: readonly Locale[] = siteConfig.languages
  .supported as readonly Locale[]

export const defaultLocale: Locale = siteConfig.languages.default as Locale
