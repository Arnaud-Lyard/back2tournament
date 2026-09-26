import { UsersIcon } from "lucide-react"
import type { Metadata } from "next"
import { notFound } from "next/navigation"
import { getTranslations } from "next-intl/server"
import { PageContainer } from "@/components/layout/page-container"
import { PageHeader } from "@/components/layout/page-header"
import { ListSearch } from "@/components/list-search"
import { Pagination } from "@/components/pagination"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import {
  Empty,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from "@/components/ui/empty"
import { loadGame } from "@/features/games/server/games"
import { loadGamePlayers } from "@/features/players/server/players"
import { readPageParam, readSearchParam } from "@/libs/list-params"
import { readUuidSegment } from "@/libs/search-params"
import { PlayerCard } from "./player-card"

interface RosterPageProps {
  params: Promise<{ gameId: string }>
  searchParams: Promise<{ page?: string | string[]; q?: string | string[] }>
}

export async function generateMetadata({
  params,
}: RosterPageProps): Promise<Metadata> {
  const gameId = readUuidSegment((await params).gameId)
  const [t, game] = await Promise.all([
    getTranslations("games.players"),
    gameId ? loadGame(gameId) : null,
  ])

  const title = game?.ok ? `${t("title")} — ${game.data.title}` : t("title")
  return { title }
}

export default async function RosterPage({
  params,
  searchParams,
}: RosterPageProps) {
  const gameId = readUuidSegment((await params).gameId)
  if (!gameId) notFound()

  const query = await searchParams
  const page = readPageParam(query.page)
  const search = readSearchParam(query.q)

  const [t, players] = await Promise.all([
    getTranslations("games.players"),
    loadGamePlayers(gameId, { page, search }),
  ])

  const pathname = `/games/${encodeURIComponent(gameId)}/players`

  return (
    <PageContainer>
      <PageHeader title={t("title")} description={t("description")} />

      <ListSearch
        pathname={pathname}
        value={search}
        placeholder={t("searchPlaceholder")}
        label={t("searchLabel")}
      />

      {!players.ok ? (
        <Alert variant="destructive">
          <AlertTitle>{t("loadError.title")}</AlertTitle>
          <AlertDescription>{t("loadError.description")}</AlertDescription>
        </Alert>
      ) : players.data.items.length === 0 ? (
        <Empty className="border">
          <EmptyHeader>
            <EmptyMedia variant="icon">
              <UsersIcon />
            </EmptyMedia>
            <EmptyTitle>{t("empty.title")}</EmptyTitle>
            <EmptyDescription>
              {search ? t("empty.filtered") : t("empty.description")}
            </EmptyDescription>
          </EmptyHeader>
        </Empty>
      ) : (
        <>
          <p className="text-sm text-muted-foreground">
            {t("count", { count: players.data.total })}
          </p>
          <ul className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            {players.data.items.map((player) => (
              <PlayerCard
                key={player.id?.value}
                player={player}
                gameId={gameId}
              />
            ))}
          </ul>
          <Pagination
            page={players.data.page}
            pages={players.data.pages}
            pathname={pathname}
            params={{ q: search }}
          />
        </>
      )}
    </PageContainer>
  )
}
