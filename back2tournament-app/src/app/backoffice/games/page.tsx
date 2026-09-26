import type { Metadata } from "next"
import { getTranslations } from "next-intl/server"
import { PageHeader } from "@/components/layout/page-header"
import { requirePermission } from "@/features/auth/rbac/require"
import { loadGames } from "@/features/games/server/games"
import { GamesManager } from "./games-manager"

export async function generateMetadata(): Promise<Metadata> {
  const t = await getTranslations("backoffice.games")
  return { title: t("title") }
}

export default async function BackofficeGamesPage() {
  const [, t, games] = await Promise.all([
    requirePermission("game:manage"),
    getTranslations("backoffice.games"),
    loadGames(),
  ])

  return (
    <>
      <PageHeader title={t("title")} description={t("description")} />
      <GamesManager games={games.ok ? games.data : null} />
    </>
  )
}
