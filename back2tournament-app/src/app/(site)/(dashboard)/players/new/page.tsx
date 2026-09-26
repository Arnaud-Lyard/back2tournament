import type { Metadata } from "next"
import Link from "next/link"
import { ArrowLeftIcon } from "lucide-react"
import { getTranslations } from "next-intl/server"
import { PageContainer } from "@/components/layout/page-container"
import { PageHeader } from "@/components/layout/page-header"
import { buttonVariants } from "@/components/ui/button"
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { findGame } from "@/features/games/lib/find-game"
import { loadGames } from "@/features/games/server/games"
import { readUuidParam } from "@/libs/search-params"
import { cn } from "@/libs/utils"
import { CreatePlayerForm } from "./create-player-form"

interface NewPlayerPageProps {
  searchParams: Promise<{ gameId?: string | string[] }>
}

const HELP_POINTS = ["onePerGame", "listed"] as const

export async function generateMetadata(): Promise<Metadata> {
  const t = await getTranslations("players")
  return { title: t("title") }
}

export default async function NewPlayerPage({
  searchParams,
}: NewPlayerPageProps) {
  const [{ gameId }, t, games] = await Promise.all([
    searchParams,
    getTranslations("players"),
    loadGames(),
  ])
  const game = readUuidParam(gameId)

  return (
    <PageContainer>
      <PageHeader
        title={t("title")}
        description={t("description")}
        actions={
          <Link
            href="/players"
            className={cn(buttonVariants({ variant: "outline" }))}
          >
            <ArrowLeftIcon data-icon="inline-start" />
            {t("back")}
          </Link>
        }
      />
      <div className="grid items-start gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        {games.ok ? (
          <CreatePlayerForm
            games={games.data}
            defaultGameId={
              game.kind === "valid" && findGame(games.data, game.id)
                ? game.id
                : undefined
            }
          />
        ) : (
          <Alert variant="destructive">
            <AlertTitle>{t("loadError.title")}</AlertTitle>
            <AlertDescription>{t("loadError.description")}</AlertDescription>
          </Alert>
        )}
        <Card>
          <CardHeader>
            <CardTitle>{t("help.title")}</CardTitle>
            <CardDescription>{t("help.description")}</CardDescription>
          </CardHeader>
          <CardContent>
            <ul className="flex list-disc flex-col gap-2 pl-4 text-muted-foreground">
              {HELP_POINTS.map((point) => (
                <li key={point}>{t(`help.${point}`)}</li>
              ))}
            </ul>
          </CardContent>
        </Card>
      </div>
    </PageContainer>
  )
}
