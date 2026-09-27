import { TrophyIcon } from "lucide-react"
import { getFormatter, getTranslations } from "next-intl/server"
import { PlayerAvatar } from "@/components/player-avatar"
import { Badge } from "@/components/ui/badge"
import { Card } from "@/components/ui/card"
import { challengeStage, formatLabel } from "@/features/fights/lib/challenge"
import type { PendingResult } from "@/features/fights/types"
import { siteConfig } from "@/features/site/config"
import { cn } from "@/libs/utils"
import { ChallengeActions } from "./challenge-actions"

interface ChallengeRowProps {
  result: PendingResult
  gameTitle?: string
}

const BADGE_VARIANT = {
  declare: "outline",
  awaiting: "secondary",
  confirm: "default",
} as const

export async function ChallengeRow({ result, gameTitle }: ChallengeRowProps) {
  const [t, format] = await Promise.all([
    getTranslations("challenges"),
    getFormatter(),
  ])

  const fightId = result.fight?.value
  const stage = challengeStage(result)
  const mine = result.side?.name ?? result.player?.battletag
  const theirs = result.opponent?.name ?? result.opponent?.player?.battletag
  const score = result.score ?? 0
  const opponentScore = result.opponent?.score ?? 0
  const opponentName = theirs ?? t("unknownOpponent")

  return (
    <li>
      <Card size="sm" className="gap-3">
        <div className="flex flex-wrap items-center justify-between gap-2 px-(--card-spacing)">
          <div className="flex flex-wrap items-center gap-2">
            <Badge variant={BADGE_VARIANT[stage]}>{t(`stage.${stage}`)}</Badge>
            <Badge variant="outline">{formatLabel(result.teamSize)}</Badge>
            {result.tournament && (
              <Badge variant="secondary">
                <TrophyIcon data-icon="inline-start" />
                {t("tournament")}
              </Badge>
            )}
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
          <Side name={mine} unknown={t("unknownPlayer")} />
          <span className="shrink-0 text-center font-mono text-sm text-muted-foreground">
            {stage === "declare" ? t("versus") : `${score} – ${opponentScore}`}
          </span>
          <Side name={theirs} unknown={t("unknownOpponent")} align="end" />
        </div>
        <p className="px-(--card-spacing) text-sm text-muted-foreground">
          {stage === "declare"
            ? t("awaitingDeclaration")
            : stage === "awaiting"
              ? t("declaredByYou", {
                  score,
                  opponentScore,
                  opponent: opponentName,
                })
              : t("declaredByOpponent", {
                  opponent: opponentName,
                  score,
                  opponentScore,
                  outcome: t(`outcome.${result.reportedStatus ?? "draw"}`),
                })}
        </p>
        {fightId && (
          <div className="px-(--card-spacing)">
            <ChallengeActions
              fightId={fightId}
              stage={stage}
              score={score}
              opponentScore={opponentScore}
              discordUrl={siteConfig.social.discord}
            />
          </div>
        )}
      </Card>
    </li>
  )
}

function Side({
  name,
  unknown,
  align = "start",
}: {
  name: string | null | undefined
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
      <PlayerAvatar battletag={name ?? "?"} size="sm" />
      <span className="truncate text-sm">{name ?? unknown}</span>
    </div>
  )
}
