import { ArrowLeftIcon } from "lucide-react"
import type { Metadata } from "next"
import Link from "next/link"
import { getTranslations } from "next-intl/server"
import { PageContainer } from "@/components/layout/page-container"
import { PageHeader } from "@/components/layout/page-header"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { buttonVariants } from "@/components/ui/button"
import { requireUser } from "@/features/auth/rbac/require"
import { loadGames } from "@/features/games/server/games"
import { cn } from "@/libs/utils"
import { CreateTournamentForm } from "./create-tournament-form"

export async function generateMetadata(): Promise<Metadata> {
  const t = await getTranslations("tournaments.create")
  return { title: t("title") }
}

export default async function NewTournamentPage() {
  const [, t, games] = await Promise.all([
    requireUser(),
    getTranslations("tournaments"),
    loadGames(),
  ])

  return (
    <PageContainer className="max-w-2xl">
      <Link
        href="/tournaments"
        className={cn(
          buttonVariants({ variant: "ghost", size: "sm" }),
          "self-start"
        )}
      >
        <ArrowLeftIcon data-icon="inline-start" />
        {t("back")}
      </Link>
      <PageHeader
        title={t("create.title")}
        description={t("create.description")}
      />
      {games.ok ? (
        <CreateTournamentForm games={games.data} />
      ) : (
        <Alert variant="destructive">
          <AlertTitle>{t("loadError.title")}</AlertTitle>
          <AlertDescription>{t("loadError.description")}</AlertDescription>
        </Alert>
      )}
    </PageContainer>
  )
}
