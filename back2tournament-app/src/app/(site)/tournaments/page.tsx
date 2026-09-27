import { CalendarIcon, PlusIcon, TrophyIcon, UsersIcon } from "lucide-react"
import type { Metadata } from "next"
import Link from "next/link"
import { getFormatter, getTranslations } from "next-intl/server"
import { ClanTag } from "@/components/clan-tag"
import { PageContainer } from "@/components/layout/page-container"
import { PageHeader } from "@/components/layout/page-header"
import { Pagination } from "@/components/pagination"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Badge } from "@/components/ui/badge"
import { buttonVariants } from "@/components/ui/button"
import { Card, CardDescription, CardTitle } from "@/components/ui/card"
import {
  Empty,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from "@/components/ui/empty"
import { getCurrentUser } from "@/features/auth/server/get-current-user"
import { formatLabel } from "@/features/fights/lib/challenge"
import { findGame } from "@/features/games/lib/find-game"
import { loadGames } from "@/features/games/server/games"
import {
  isValidStatus,
  STATUS_VARIANT,
} from "@/features/tournaments/lib/status"
import { loadTournaments } from "@/features/tournaments/server/tournaments"
import {
  TOURNAMENT_STATUSES,
  type TournamentStatus,
} from "@/features/tournaments/types"
import { listHref, readPageParam } from "@/libs/list-params"
import { cn } from "@/libs/utils"

export async function generateMetadata(): Promise<Metadata> {
  const t = await getTranslations("tournaments")
  return { title: t("title") }
}

export default async function TournamentsPage({
  searchParams,
}: {
  searchParams: Promise<{
    page?: string | string[]
    status?: string | string[]
  }>
}) {
  const query = await searchParams
  const page = readPageParam(query.page)
  const rawStatus = Array.isArray(query.status) ? query.status[0] : query.status
  const status: TournamentStatus | undefined =
    rawStatus && isValidStatus(rawStatus) ? rawStatus : undefined

  const [t, format, tournaments, games, user] = await Promise.all([
    getTranslations("tournaments"),
    getFormatter(),
    loadTournaments({ page, status }),
    loadGames(),
    getCurrentUser(),
  ])

  return (
    <PageContainer>
      <PageHeader
        title={t("title")}
        description={t("description")}
        actions={
          user ? (
            <Link href="/tournaments/new" className={cn(buttonVariants())}>
              <PlusIcon data-icon="inline-start" />
              {t("organize")}
            </Link>
          ) : null
        }
      />

      <nav aria-label={t("filterLabel")} className="flex flex-wrap gap-2">
        {[undefined, ...TOURNAMENT_STATUSES].map((option) => (
          <Link
            key={option ?? "all"}
            href={listHref("/tournaments", { status: option })}
            aria-current={option === status ? "page" : undefined}
            className={cn(
              buttonVariants({
                variant: option === status ? "default" : "outline",
                size: "sm",
              })
            )}
          >
            {option ? t(`status.${option}`) : t("all")}
          </Link>
        ))}
      </nav>

      {!tournaments.ok ? (
        <Alert variant="destructive">
          <AlertTitle>{t("loadError.title")}</AlertTitle>
          <AlertDescription>{t("loadError.description")}</AlertDescription>
        </Alert>
      ) : tournaments.data.items.length === 0 ? (
        <Empty className="border">
          <EmptyHeader>
            <EmptyMedia variant="icon">
              <TrophyIcon />
            </EmptyMedia>
            <EmptyTitle>{t("empty.title")}</EmptyTitle>
            <EmptyDescription>{t("empty.description")}</EmptyDescription>
          </EmptyHeader>
        </Empty>
      ) : (
        <>
          <ul className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            {tournaments.data.items.map((tournament) => {
              const id = tournament.id?.value
              if (!id) return null
              const game = games.ok
                ? findGame(games.data, tournament.game?.value)
                : undefined

              return (
                <li key={id}>
                  <Card className="group h-full gap-0 p-0 transition-colors hover:border-primary/50">
                    <Link
                      href={`/tournaments/${encodeURIComponent(id)}`}
                      className="flex h-full flex-col gap-3 rounded-[inherit] p-4 outline-none focus-visible:ring-3 focus-visible:ring-ring/50"
                    >
                      <div className="flex flex-wrap items-center gap-2">
                        {tournament.status && (
                          <Badge variant={STATUS_VARIANT[tournament.status]}>
                            {t(`status.${tournament.status}`)}
                          </Badge>
                        )}
                        <Badge variant="outline">
                          {formatLabel(tournament.teamSize)}
                        </Badge>
                      </div>
                      <CardTitle className="truncate">
                        {tournament.name}
                      </CardTitle>
                      <CardDescription className="flex flex-col gap-1">
                        <span>{game?.title ?? t("unknownGame")}</span>
                        <span className="inline-flex items-center gap-1">
                          <UsersIcon className="size-4" />
                          {t("seats", {
                            count: tournament.participantCount ?? 0,
                            capacity: tournament.capacity ?? 0,
                          })}
                        </span>
                        {tournament.startsAt && (
                          <span className="inline-flex items-center gap-1">
                            <CalendarIcon className="size-4" />
                            {format.dateTime(new Date(tournament.startsAt), {
                              dateStyle: "medium",
                              timeStyle: "short",
                            })}
                          </span>
                        )}
                        {tournament.winner?.name && (
                          <span className="inline-flex items-center gap-1 font-medium text-foreground">
                            <TrophyIcon className="size-4" />
                            <ClanTag tag={tournament.winner.tag} />
                            {tournament.winner.name}
                          </span>
                        )}
                      </CardDescription>
                    </Link>
                  </Card>
                </li>
              )
            })}
          </ul>
          <Pagination
            page={tournaments.data.page}
            pages={tournaments.data.pages}
            pathname="/tournaments"
            params={{ status }}
          />
        </>
      )}
    </PageContainer>
  )
}
