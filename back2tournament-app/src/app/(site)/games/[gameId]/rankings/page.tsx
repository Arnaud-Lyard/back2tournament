import { TrophyIcon } from "lucide-react"
import type { Metadata } from "next"
import Link from "next/link"
import { notFound } from "next/navigation"
import { getTranslations } from "next-intl/server"
import { PageContainer } from "@/components/layout/page-container"
import { PageHeader } from "@/components/layout/page-header"
import { Pagination } from "@/components/pagination"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Badge } from "@/components/ui/badge"
import {
  Empty,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from "@/components/ui/empty"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { getCurrentUser } from "@/features/auth/server/get-current-user"
import { activeClanIn } from "@/features/clans/lib/membership"
import { loadMyClans } from "@/features/clans/server/clans"
import { loadGame } from "@/features/games/server/games"
import {
  rankedSubjectHref,
  rankingHref,
  readRankingView,
} from "@/features/rankings/lib/ranking"
import { loadRanking } from "@/features/rankings/server/rankings"
import type { RankingView } from "@/features/rankings/types"
import { readPageParam } from "@/libs/list-params"
import { readUuidSegment } from "@/libs/search-params"
import { cn } from "@/libs/utils"

interface RankingsPageProps {
  params: Promise<{ gameId: string }>
  searchParams: Promise<{
    page?: string | string[]
    view?: string | string[]
  }>
}

const VIEWS: readonly RankingView[] = ["players", "clans"]

export async function generateMetadata({
  params,
}: RankingsPageProps): Promise<Metadata> {
  const gameId = readUuidSegment((await params).gameId)
  const [t, game] = await Promise.all([
    getTranslations("rankings"),
    gameId ? loadGame(gameId) : null,
  ])

  const title = game?.ok ? `${t("title")} — ${game.data.title}` : t("title")
  return { title }
}

export default async function RankingsPage({
  params,
  searchParams,
}: RankingsPageProps) {
  const gameId = readUuidSegment((await params).gameId)
  if (!gameId) notFound()

  const query = await searchParams
  const page = readPageParam(query.page)
  const view = readRankingView(query.view)

  const [t, ranking, user, myClans] = await Promise.all([
    getTranslations("rankings"),
    loadRanking(gameId, view, page),
    getCurrentUser(),
    loadMyClans(),
  ])

  if (!ranking.ok && ranking.status === 404) notFound()

  // The caller's own place stands out: their profile, or their clan.
  const mine =
    view === "clans"
      ? (myClans.ok ? activeClanIn(myClans.data, gameId) : undefined)?.clan.id
          ?.value
      : user?.playersByGame[gameId]?.id
  const pathname = `/games/${encodeURIComponent(gameId)}/rankings`

  return (
    <PageContainer>
      <PageHeader title={t("title")} description={t("description")} />

      <nav
        aria-label={t("views.label")}
        className="flex w-fit items-center gap-1 rounded-lg bg-muted p-1"
      >
        {VIEWS.map((candidate) => (
          <Link
            key={candidate}
            href={rankingHref(gameId, candidate)}
            aria-current={candidate === view ? "page" : undefined}
            className={cn(
              "rounded-md px-3 py-1 text-sm font-medium transition-colors",
              candidate === view
                ? "bg-background text-foreground shadow-sm"
                : "text-muted-foreground hover:text-foreground"
            )}
          >
            {t(`views.${candidate}`)}
          </Link>
        ))}
      </nav>

      <p className="text-sm text-muted-foreground">{t("howItWorks")}</p>

      {!ranking.ok ? (
        <Alert variant="destructive">
          <AlertTitle>{t("loadError.title")}</AlertTitle>
          <AlertDescription>{t("loadError.description")}</AlertDescription>
        </Alert>
      ) : ranking.data.items.length === 0 ? (
        <Empty className="border">
          <EmptyHeader>
            <EmptyMedia variant="icon">
              <TrophyIcon />
            </EmptyMedia>
            <EmptyTitle>{t("empty.title")}</EmptyTitle>
            <EmptyDescription>{t(`empty.${view}`)}</EmptyDescription>
          </EmptyHeader>
        </Empty>
      ) : (
        <>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead className="w-16">{t("columns.rank")}</TableHead>
                <TableHead>
                  {t(view === "clans" ? "columns.clan" : "columns.player")}
                </TableHead>
                <TableHead className="text-right">
                  {t("columns.rating")}
                </TableHead>
                <TableHead className="text-right">
                  {t("columns.fights")}
                </TableHead>
                <TableHead className="text-right">
                  {t("columns.record")}
                </TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {ranking.data.items.map((entry) => {
                const href = rankedSubjectHref(gameId, entry.subject)
                const isMine = !!mine && entry.subject.id?.value === mine
                const name = entry.subject.name ?? t("unknown")

                return (
                  <TableRow
                    key={entry.subject.id?.value}
                    data-state={isMine ? "selected" : undefined}
                  >
                    <TableCell className="font-mono tabular-nums">
                      {entry.rank <= 3 ? (
                        <span className="inline-flex items-center gap-1 font-semibold">
                          <TrophyIcon
                            className={cn(
                              "size-4",
                              entry.rank === 1
                                ? "text-yellow-500"
                                : entry.rank === 2
                                  ? "text-zinc-400"
                                  : "text-amber-700"
                            )}
                          />
                          {entry.rank}
                        </span>
                      ) : (
                        entry.rank
                      )}
                    </TableCell>
                    <TableCell>
                      <span className="flex min-w-0 items-center gap-2">
                        {entry.subject.tag && (
                          <Badge variant="secondary" className="font-mono">
                            {entry.subject.tag}
                          </Badge>
                        )}
                        {href ? (
                          <Link
                            href={href}
                            className="truncate font-medium underline-offset-4 hover:underline"
                          >
                            {name}
                          </Link>
                        ) : (
                          <span className="truncate font-medium">{name}</span>
                        )}
                        {isMine && <Badge variant="outline">{t("you")}</Badge>}
                      </span>
                    </TableCell>
                    <TableCell className="text-right font-mono font-semibold tabular-nums">
                      {entry.rating}
                    </TableCell>
                    <TableCell className="text-right tabular-nums">
                      {entry.fights}
                    </TableCell>
                    <TableCell className="text-right whitespace-nowrap text-muted-foreground tabular-nums">
                      {entry.wins} – {entry.draws} – {entry.losses}
                    </TableCell>
                  </TableRow>
                )
              })}
            </TableBody>
          </Table>
          <Pagination
            page={ranking.data.page}
            pages={ranking.data.pages}
            pathname={pathname}
            params={{ view: view === "clans" ? "clans" : undefined }}
          />
        </>
      )}
    </PageContainer>
  )
}
