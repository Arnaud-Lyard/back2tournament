import { SwordsIcon } from "lucide-react"
import type { Metadata } from "next"
import Link from "next/link"
import { getTranslations } from "next-intl/server"
import { PageContainer } from "@/components/layout/page-container"
import { PageHeader } from "@/components/layout/page-header"
import { Pagination } from "@/components/pagination"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { buttonVariants } from "@/components/ui/button"
import {
  Empty,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from "@/components/ui/empty"
import { loadChallenges } from "@/features/fights/server/fights"
import { findGame } from "@/features/games/lib/find-game"
import { loadGames } from "@/features/games/server/games"
import { readPageParam } from "@/libs/list-params"
import { cn } from "@/libs/utils"
import { ChallengeRow } from "./challenge-row"

export async function generateMetadata(): Promise<Metadata> {
  const t = await getTranslations("challenges")
  return { title: t("title") }
}

export default async function ChallengesPage({
  searchParams,
}: {
  searchParams: Promise<{ page?: string | string[] }>
}) {
  const page = readPageParam((await searchParams).page)

  const [t, challenges, games] = await Promise.all([
    getTranslations("challenges"),
    loadChallenges({ page }),
    loadGames(),
  ])

  return (
    <PageContainer>
      <PageHeader title={t("title")} description={t("description")} />

      {!challenges.ok ? (
        <Alert variant="destructive">
          <AlertTitle>{t("loadError.title")}</AlertTitle>
          <AlertDescription>{t("loadError.description")}</AlertDescription>
        </Alert>
      ) : challenges.data.items.length === 0 ? (
        <Empty className="border">
          <EmptyHeader>
            <EmptyMedia variant="icon">
              <SwordsIcon />
            </EmptyMedia>
            <EmptyTitle>{t("empty.title")}</EmptyTitle>
            <EmptyDescription>{t("empty.description")}</EmptyDescription>
          </EmptyHeader>
          <Link href="/" className={cn(buttonVariants({ variant: "outline" }))}>
            {t("empty.action")}
          </Link>
        </Empty>
      ) : (
        <>
          <ul className="flex flex-col gap-3">
            {challenges.data.items.map((result) => (
              <ChallengeRow
                key={result.id?.value}
                result={result}
                gameTitle={
                  games.ok
                    ? findGame(games.data, result.game?.value)?.title
                    : undefined
                }
              />
            ))}
          </ul>
          <Pagination
            page={challenges.data.page}
            pages={challenges.data.pages}
            pathname="/challenges"
          />
        </>
      )}
    </PageContainer>
  )
}
