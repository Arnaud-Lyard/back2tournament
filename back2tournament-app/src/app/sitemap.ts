import type { MetadataRoute } from "next"
import { loadPublicArticles } from "@/features/blog/server/blog"
import { loadPublicGames } from "@/features/games/server/games"
import { baseUrl } from "@/features/site/config"

const STATIC_ROUTES = ["", "/blog"] as const

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const lastModified = new Date()

  const entries: MetadataRoute.Sitemap = STATIC_ROUTES.map((path) => ({
    url: `${baseUrl}${path}`,
    lastModified,
    changeFrequency: "weekly" as const,
    priority: path === "" ? 1 : 0.8,
  }))

  for (const article of await loadPublicArticles()) {
    const id = article.id?.value
    if (!id) continue

    entries.push({
      url: `${baseUrl}/blog/${id}`,
      lastModified: article.updatedAt
        ? new Date(article.updatedAt)
        : lastModified,
      changeFrequency: "monthly",
      priority: 0.6,
    })
  }

  for (const game of await loadPublicGames()) {
    const id = game.id?.value
    if (!id) continue

    for (const section of ["players", "clans"]) {
      entries.push({
        url: `${baseUrl}/games/${id}/${section}`,
        lastModified,
        changeFrequency: "daily",
        priority: 0.7,
      })
    }
  }

  return entries
}

export const revalidate = 3600
