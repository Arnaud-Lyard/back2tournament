import { ArrowLeftIcon } from "lucide-react"
import type { Metadata } from "next"
import Link from "next/link"
import { notFound } from "next/navigation"
import { getFormatter, getTranslations } from "next-intl/server"
import { PageContainer } from "@/components/layout/page-container"
import { PlayerAvatar } from "@/components/player-avatar"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { buttonVariants } from "@/components/ui/button"
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { Separator } from "@/components/ui/separator"
import { loadGame } from "@/features/games/server/games"
import { loadPlayer } from "@/features/players/server/players"
import { readUuidSegment } from "@/libs/search-params"
import { cn } from "@/libs/utils"
import { ChallengeButton } from "./challenge-button"

interface PlayerPageProps {
  params: Promise<{ gameId: string; playerId: string }>
}

export async function generateMetadata({
  params,
}: PlayerPageProps): Promise<Metadata> {
  const playerId = readUuidSegment((await params).playerId)
  if (!playerId) return {}

  const player = await loadPlayer(playerId)
  return player.ok ? { title: player.data.battletag ?? "" } : {}
}

export default async function PlayerPage({ params }: PlayerPageProps) {
  const { gameId: rawGameId, playerId: rawPlayerId } = await params
  const gameId = readUuidSegment(rawGameId)
  const playerId = readUuidSegment(rawPlayerId)
  if (!gameId || !playerId) notFound()

  const [t, format, player, game] = await Promise.all([
    getTranslations("games.player"),
    getFormatter(),
    loadPlayer(playerId),
    loadGame(gameId),
  ])

  if (!player.ok) {
    if (player.status === 404) notFound()

    return (
      <PageContainer>
        <Alert variant="destructive">
          <AlertTitle>{t("loadError.title")}</AlertTitle>
          <AlertDescription>{t("loadError.description")}</AlertDescription>
        </Alert>
      </PageContainer>
    )
  }

  if (player.data.game?.value !== gameId) notFound()

  const battletag = player.data.battletag ?? ""
  const roster = `/games/${encodeURIComponent(gameId)}/players`

  return (
    <PageContainer className="max-w-3xl">
      <Link
        href={roster}
        className={cn(
          buttonVariants({ variant: "ghost", size: "sm" }),
          "self-start"
        )}
      >
        <ArrowLeftIcon data-icon="inline-start" />
        {t("back")}
      </Link>

      <Card>
        <CardHeader className="flex items-center gap-4">
          <PlayerAvatar battletag={battletag} size="lg" className="size-16" />
          <div className="flex flex-col gap-1">
            <CardTitle className="text-2xl">{battletag}</CardTitle>
            <CardDescription>
              {game.ok ? game.data.title : t("unknownGame")}
            </CardDescription>
          </div>
        </CardHeader>
        <CardContent className="flex flex-col gap-4">
          {player.data.createdAt && (
            <p className="text-sm text-muted-foreground">
              {t("since", {
                date: format.dateTime(new Date(player.data.createdAt), {
                  dateStyle: "long",
                }),
              })}
            </p>
          )}
          <Separator />
          <ChallengeButton
            gameId={gameId}
            playerId={playerId}
            battletag={battletag}
          />
        </CardContent>
      </Card>
    </PageContainer>
  )
}
