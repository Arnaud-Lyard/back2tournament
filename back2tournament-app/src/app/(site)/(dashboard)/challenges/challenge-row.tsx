import { getFormatter, getTranslations } from "next-intl/server"
import { PlayerAvatar } from "@/components/player-avatar"
import { Badge } from "@/components/ui/badge"
import { Card } from "@/components/ui/card"
import type { PendingResult } from "@/features/fights/types"
import { cn } from "@/libs/utils"

interface ChallengeRowProps {
  result: PendingResult
  gameTitle?: string
}

const BADGE_VARIANT = {
  pending: "outline",
  reporting: "default",
} as const

export async function ChallengeRow({ result, gameTitle }: ChallengeRowProps) {
  const [t, format] = await Promise.all([
    getTranslations("challenges"),
    getFormatter(),
  ])

  const status = result.status === "reporting" ? "reporting" : "pending"
  const reported = result.reportedStatus

  return (
    <li>
      <Card size="sm" className="gap-3">
        <div className="flex flex-wrap items-center justify-between gap-2 px-(--card-spacing)">
          <div className="flex flex-wrap items-center gap-2">
            <Badge variant={BADGE_VARIANT[status]}>
              {t(`status.${status}`)}
            </Badge>
            {gameTitle && (
              <span className="text-xs text-muted-foreground">{gameTitle}</span>
            )}
          </div>
          {result.createdAt && (
            <time
              dateTime={result.createdAt}
              className="text-xs text-muted-foreground"
            >
              {format.dateTime(new Date(result.createdAt), {
                dateStyle: "medium",
              })}
            </time>
          )}
        </div>
        <div className="flex items-center gap-3 px-(--card-spacing)">
          <Side
            battletag={result.player?.battletag}
            unknown={t("unknownPlayer")}
          />
          <span className="shrink-0 text-center font-mono text-sm text-muted-foreground">
            {t("versus")}
          </span>
          <Side
            battletag={result.opponent?.player?.battletag}
            unknown={t("unknownOpponent")}
            align="end"
          />
        </div>
        <p className="px-(--card-spacing) text-sm text-muted-foreground">
          {reported
            ? t("claimed", {
                outcome: t(`outcome.${reported}`),
                score: result.score ?? 0,
              })
            : t("awaitingDeclaration")}
        </p>
      </Card>
    </li>
  )
}

function Side({
  battletag,
  unknown,
  align = "start",
}: {
  battletag: string | null | undefined
  unknown: string
  align?: "start" | "end"
}) {
  return (
    <div
      className={cn(
        "flex min-w-0 flex-1 items-center gap-2",
        align === "end" && "flex-row-reverse text-right"
      )}
    >
      <PlayerAvatar battletag={battletag ?? "?"} size="sm" />
      <span className="truncate text-sm">{battletag ?? unknown}</span>
    </div>
  )
}
