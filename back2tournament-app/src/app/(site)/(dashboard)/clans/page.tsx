import { ArrowRightIcon, ShieldIcon } from "lucide-react"
import type { Metadata } from "next"
import Link from "next/link"
import { getTranslations } from "next-intl/server"
import { PageContainer } from "@/components/layout/page-container"
import { PageHeader } from "@/components/layout/page-header"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Badge } from "@/components/ui/badge"
import { buttonVariants } from "@/components/ui/button"
import { Card } from "@/components/ui/card"
import {
  Empty,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from "@/components/ui/empty"
import { loadMyClans } from "@/features/clans/server/clans"
import { findGame } from "@/features/games/lib/find-game"
import { loadGames } from "@/features/games/server/games"
import { cn } from "@/libs/utils"

export async function generateMetadata(): Promise<Metadata> {
  const t = await getTranslations("myClans")
  return { title: t("title") }
}

export default async function MyClansPage() {
  const [t, myClans, games] = await Promise.all([
    getTranslations("myClans"),
    loadMyClans(),
    loadGames(),
  ])

  return (
    <PageContainer>
      <PageHeader title={t("title")} description={t("description")} />

      {!myClans.ok ? (
        <Alert variant="destructive">
          <AlertTitle>{t("loadError.title")}</AlertTitle>
          <AlertDescription>{t("loadError.description")}</AlertDescription>
        </Alert>
      ) : myClans.data.length === 0 ? (
        <Empty className="border">
          <EmptyHeader>
            <EmptyMedia variant="icon">
              <ShieldIcon />
            </EmptyMedia>
            <EmptyTitle>{t("empty.title")}</EmptyTitle>
            <EmptyDescription>{t("empty.description")}</EmptyDescription>
          </EmptyHeader>
          <Link href="/" className={cn(buttonVariants({ variant: "outline" }))}>
            {t("empty.action")}
          </Link>
        </Empty>
      ) : (
        <ul className="flex flex-col gap-3">
          {myClans.data.map(({ clan, membership, requests }) => {
            const clanId = clan.id?.value
            const gameId = clan.game?.value
            if (!clanId || !gameId) return null

            const game = games.ok ? findGame(games.data, gameId) : undefined
            const awaitsMe = membership.status === "invited" || requests > 0

            return (
              <li key={membership.id?.value}>
                <Card
                  size="sm"
                  className="flex flex-row flex-wrap items-center justify-between gap-3 px-(--card-spacing)"
                >
                  <div className="flex min-w-0 flex-col gap-1">
                    <div className="flex flex-wrap items-center gap-2">
                      <Badge variant="secondary" className="font-mono">
                        {clan.tag}
                      </Badge>
                      <span className="font-medium">{clan.name}</span>
                      {membership.status === "invited" ? (
                        <Badge>{t("invited")}</Badge>
                      ) : membership.status === "requested" ? (
                        <Badge variant="outline">{t("requested")}</Badge>
                      ) : membership.role === "leader" ? (
                        <Badge variant="outline">{t("leader")}</Badge>
                      ) : null}
                      {requests > 0 && (
                        <Badge>{t("requests", { count: requests })}</Badge>
                      )}
                    </div>
                    <span className="text-xs text-muted-foreground">
                      {t("as", {
                        battletag: membership.player?.battletag ?? "",
                        game: game?.title ?? t("unknownGame"),
                      })}
                    </span>
                  </div>
                  <Link
                    href={`/games/${encodeURIComponent(gameId)}/clans/${encodeURIComponent(clanId)}`}
                    className={cn(
                      buttonVariants({
                        variant: awaitsMe ? "default" : "outline",
                        size: "sm",
                      })
                    )}
                  >
                    {awaitsMe ? t("answer") : t("open")}
                    <ArrowRightIcon data-icon="inline-end" />
                  </Link>
                </Card>
              </li>
            )
          })}
        </ul>
      )}
    </PageContainer>
  )
}
