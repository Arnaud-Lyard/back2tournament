import { ArrowRightIcon, Gamepad2Icon } from "lucide-react"
import Link from "next/link"
import { getTranslations } from "next-intl/server"
import { PageContainer } from "@/components/layout/page-container"
import { PageHeader } from "@/components/layout/page-header"
import { StoredImage } from "@/components/stored-image"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Card, CardDescription, CardTitle } from "@/components/ui/card"
import {
  Empty,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from "@/components/ui/empty"
import { loadGames } from "@/features/games/server/games"
import type { Game } from "@/features/games/types"

export default async function HomePage() {
  const [t, games] = await Promise.all([getTranslations("home"), loadGames()])

  return (
    <PageContainer>
      <PageHeader title={t("title")} description={t("description")} />

      {!games.ok ? (
        <Alert variant="destructive">
          <AlertTitle>{t("loadError.title")}</AlertTitle>
          <AlertDescription>{t("loadError.description")}</AlertDescription>
        </Alert>
      ) : games.data.length === 0 ? (
        <Empty className="border">
          <EmptyHeader>
            <EmptyMedia variant="icon">
              <Gamepad2Icon />
            </EmptyMedia>
            <EmptyTitle>{t("empty.title")}</EmptyTitle>
            <EmptyDescription>{t("empty.description")}</EmptyDescription>
          </EmptyHeader>
        </Empty>
      ) : (
        <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {games.data.map((game) => (
            <GameCard key={game.id?.value} game={game} enter={t("enter")} />
          ))}
        </ul>
      )}
    </PageContainer>
  )
}

function GameCard({ game, enter }: { game: Game; enter: string }) {
  const id = game.id?.value
  if (!id) return null

  return (
    <li>
      <Card className="group h-full gap-0 overflow-hidden p-0 transition-colors hover:border-primary/50">
        <Link
          href={`/games/${encodeURIComponent(id)}/players`}
          className="flex h-full flex-col rounded-[inherit] outline-none focus-visible:ring-3 focus-visible:ring-ring/50"
        >
          <StoredImage src={game.image} />
          <div className="flex flex-1 flex-col gap-1.5 p-4">
            <CardTitle>{game.title}</CardTitle>
            <CardDescription className="mt-auto inline-flex items-center gap-1 text-primary">
              {enter}
              <ArrowRightIcon className="size-4 transition-transform group-hover:translate-x-0.5" />
            </CardDescription>
          </div>
        </Link>
      </Card>
    </li>
  )
}
