import { TrophyIcon } from "lucide-react"
import Link from "next/link"
import { getTranslations } from "next-intl/server"
import { Badge } from "@/components/ui/badge"
import { buttonVariants } from "@/components/ui/button"
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { formatLabel } from "@/features/fights/lib/challenge"
import { rankingHref } from "@/features/rankings/lib/ranking"
import type { SubjectRating } from "@/features/rankings/types"
import type { Loaded } from "@/libs/api/load"
import { cn } from "@/libs/utils"

interface RatingCardProps {
  rating: Loaded<SubjectRating>
  gameId: string
  subject: "player" | "clan"
}

export async function RatingCard({ rating, gameId, subject }: RatingCardProps) {
  const t = await getTranslations("rankings.card")
  const view = subject === "clan" ? "clans" : "players"

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
            href={rankingHref(gameId, view)}
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
          <ul className="flex flex-col divide-y">
            {rating.data.ratings.map((format) => (
              <li
                key={format.teamSize}
                className="flex items-start gap-3 py-3 first:pt-0 last:pb-0"
              >
                <Badge
                  variant="outline"
                  className="mt-1 w-12 shrink-0 justify-center"
                >
                  {formatLabel(format.teamSize)}
                </Badge>
                <div className="flex min-w-0 flex-1 flex-col gap-0.5">
                  <div className="flex flex-wrap items-baseline justify-between gap-x-3">
                    <span className="font-mono text-xl font-semibold tabular-nums">
                      {format.rating}
                      <span className="ml-1 text-xs font-normal text-muted-foreground">
                        {t("rating")}
                      </span>
                    </span>
                    <Link
                      href={rankingHref(gameId, view, format.teamSize)}
                      className="text-sm underline-offset-4 hover:underline"
                    >
                      {format.rank === null ? (
                        <span className="text-muted-foreground">
                          {t("unranked")}
                        </span>
                      ) : (
                        <>
                          <span className="font-semibold tabular-nums">
                            #{format.rank}
                          </span>{" "}
                          <span className="text-muted-foreground">
                            {t("outOf", { total: format.total })}
                          </span>
                        </>
                      )}
                    </Link>
                  </div>
                  <span className="text-xs text-muted-foreground tabular-nums">
                    {t("recordValue", {
                      wins: format.wins,
                      draws: format.draws,
                      losses: format.losses,
                    })}
                    {" · "}
                    {t("fights", { count: format.fights })}
                  </span>
                </div>
              </li>
            ))}
          </ul>
        )}
      </CardContent>
    </Card>
  )
}
