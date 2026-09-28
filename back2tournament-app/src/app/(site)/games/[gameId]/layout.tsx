import { notFound } from "next/navigation"
import { getTranslations } from "next-intl/server"
import type { ReactNode } from "react"
import { GameNav } from "@/components/layout/game-nav"
import { PageContainer } from "@/components/layout/page-container"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { loadGame } from "@/features/games/server/games"
import { readUuidSegment } from "@/libs/search-params"

interface GameLayoutProps {
  children: ReactNode
  params: Promise<{ gameId: string }>
}

export default async function GameLayout({
  children,
  params,
}: GameLayoutProps) {
  const gameId = readUuidSegment((await params).gameId)
  if (!gameId) notFound()

  const [t, game] = await Promise.all([
    getTranslations("games"),
    loadGame(gameId),
  ])

  if (!game.ok) {
    if (game.status === 404) notFound()

    return (
      <PageContainer>
        <Alert variant="destructive">
          <AlertTitle>{t("loadError.title")}</AlertTitle>
          <AlertDescription>{t("loadError.description")}</AlertDescription>
        </Alert>
      </PageContainer>
    )
  }

  return (
    <>
      <GameNav
        gameId={gameId}
        title={game.data.title ?? ""}
        image={game.data.image}
      />
      {children}
    </>
  )
}
