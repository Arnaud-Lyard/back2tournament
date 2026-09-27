import { TrophyIcon } from "lucide-react"
import Link from "next/link"
import { getTranslations } from "next-intl/server"
import { buttonVariants } from "@/components/ui/button"
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { rankingHref } from "@/features/rankings/lib/ranking"
import type { SubjectRating } from "@/features/rankings/types"
import type { Loaded } from "@/libs/api/load"
import { cn } from "@/libs/utils"

interface RatingCardProps {
  rating: Loaded<SubjectRating>
  gameId: string
  /** A player profile rates on its duels, a clan on its teams' fights. */
  subject: "player" | "clan"
}

/** The Elo rating of a player profile or a clan, its rank and its record. */
export async function RatingCard({ rating, gameId, subject }: RatingCardProps) {
  const t = await getTranslations("rankings.card")

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <TrophyIcon className="size-4" />
          {t("title")}
        </CardTitle>
        <CardDescription>{t(`description.${subject}`)}</CardDescription>
        <CardAction>
          <Link
            href={rankingHref(gameId, subject === "clan" ? "clans" : "players")}
            className={cn(buttonVariants({ variant: "outline", size: "sm" }))}
          >
            {t("viewRanking")}
          </Link>
        </CardAction>
      </CardHeader>
      <CardContent>
        {!rating.ok ? (
          <p className="text-sm text-muted-foreground">{t("loadError")}</p>
        ) : (
          <dl className="grid grid-cols-3 gap-4">
            <div className="flex flex-col gap-1">
              <dt className="text-xs text-muted-foreground">{t("rating")}</dt>
              <dd className="font-mono text-2xl font-semibold tabular-nums">
                {rating.data.rating}
              </dd>
            </div>
            <div className="flex flex-col gap-1">
              <dt className="text-xs text-muted-foreground">{t("rank")}</dt>
              <dd className="text-2xl font-semibold tabular-nums">
                {rating.data.rank === null ? (
                  <span className="text-base font-normal text-muted-foreground">
                    {t("unranked")}
                  </span>
                ) : (
                  <>
                    #{rating.data.rank}
                    <span className="text-sm font-normal text-muted-foreground">
                      {" "}
                      {t("outOf", { total: rating.data.total })}
                    </span>
                  </>
                )}
              </dd>
            </div>
            <div className="flex flex-col gap-1">
              <dt className="text-xs text-muted-foreground">{t("record")}</dt>
              <dd className="text-sm tabular-nums">
                {t("recordValue", {
                  wins: rating.data.wins,
                  draws: rating.data.draws,
                  losses: rating.data.losses,
                })}
                <span className="block text-xs text-muted-foreground">
                  {t("fights", { count: rating.data.fights })}
                </span>
              </dd>
            </div>
          </dl>
        )}
      </CardContent>
    </Card>
  )
}
