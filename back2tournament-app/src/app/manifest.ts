import type { MetadataRoute } from "next"
import { siteConfig } from "@/features/site/config"

export default function manifest(): MetadataRoute.Manifest {
  return {
    name: siteConfig.appName,
    short_name: siteConfig.appName,
    description: siteConfig.description,
    start_url: "/",
    scope: "/",
    lang: siteConfig.languages.default,
    display: "standalone",
    orientation: "portrait-primary",
    background_color: siteConfig.theme.light,
    theme_color: siteConfig.theme.light,
    icons: [
      {
        src: siteConfig.icons.favicon,
        sizes: "48x48",
        type: "image/x-icon",
        purpose: "any",
      },
      {
        src: siteConfig.icons.icon,
        sizes: "any",
        type: "image/svg+xml",
        purpose: "maskable",
      },
    ],
  }
}
