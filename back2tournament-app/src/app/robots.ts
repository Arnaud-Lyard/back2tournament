import type { MetadataRoute } from "next"
import { baseUrl } from "@/features/site/config"

const PRIVATE_PATHS = [
  "/api/",
  "/login",
  "/register",
  "/verify-email",
  "/403",
  "/players/new",
  "/account",
  "/backoffice",
]

export default function robots(): MetadataRoute.Robots {
  return {
    rules: [
      {
        userAgent: "*",
        allow: "/",
        disallow: PRIVATE_PATHS,
      },
    ],
    sitemap: `${baseUrl}/sitemap.xml`,
    host: baseUrl,
  }
}
