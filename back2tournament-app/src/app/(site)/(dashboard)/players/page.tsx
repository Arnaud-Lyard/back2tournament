import type { Metadata } from "next"
import Link from "next/link"
import { PlusIcon } from "lucide-react"
import { getTranslations } from "next-intl/server"
import { PageContainer } from "@/components/layout/page-container"
import { PageHeader } from "@/components/layout/page-header"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { buttonVariants } from "@/components/ui/button"
import { loadGames } from "@/features/games/server/games"
import { cn } from "@/libs/utils"
import { PlayerProfiles } from "./player-profiles"

export async function generateMetadata(): Promise<Metadata> {
  const t = await getTranslations("players.profiles")
  return { title: t("title") }
}

export default async function PlayerProfilesPage() {
  const [t, games] = await Promise.all([
    getTranslations("players"),
    loadGames(),
  ])

  return (
    <PageContainer>
      <PageHeader
        title={t("profiles.title")}
        description={t("profiles.description")}
        actions={
          <Link href="/players/new" className={cn(buttonVariants())}>
            <PlusIcon data-icon="inline-start" />
            {t("profiles.create")}
          </Link>
        }
      />
      {!games.ok && (
        <Alert variant="destructive">
          <AlertTitle>{t("loadError.title")}</AlertTitle>
          <AlertDescription>{t("loadError.description")}</AlertDescription>
        </Alert>
      )}
      <PlayerProfiles games={games.ok ? games.data : []} />
    </PageContainer>
  )
}
