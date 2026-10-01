import { HistoryIcon, TrophyIcon } from "lucide-react"
import Link from "next/link"
import { getFormatter, getTranslations } from "next-intl/server"
import { ClanTag } from "@/components/clan-tag"
import { Badge } from "@/components/ui/badge"
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import {
  Empty,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from "@/components/ui/empty"
import { formatLabel } from "@/features/fights/lib/challenge"
import { OUTCOME_VARIANT, opponentHref } from "@/features/fights/lib/history"
import type { SettledResultPage } from "@/features/fights/types"
import type { Loaded } from "@/libs/api/load"

interface ResultHistoryProps {
  history: Loaded<SettledResultPage>
  subject: "player" | "clan"
}

export async function ResultHistory({ history, subject }: ResultHistoryProps) {
  const [t, format] = await Promise.all([
    getTranslations("history"),
    getFormatter(),
  ])

  const items = history.ok ? (history.data.items ?? []) : []

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <HistoryIcon className="size-4" />
          {t("title")}
        </CardTitle>
        <CardDescription>{t(`description.${subject}`)}</CardDescription>
      </CardHeader>
      <CardContent className="flex flex-col gap-3">
        {!history.ok ? (
          <p className="text-sm text-muted-foreground">{t("loadError")}</p>
        ) : items.length === 0 ? (
          <Empty>
            <EmptyHeader>
              <EmptyMedia variant="icon">
                <HistoryIcon />
              </EmptyMedia>
              <EmptyTitle>{t("empty.title")}</EmptyTitle>
              <EmptyDescription>{t(`empty.${subject}`)}</EmptyDescription>
            </EmptyHeader>
          </Empty>
        ) : (
          <ul className="flex flex-col divide-y">
            {items.map((result) => {
              const href = opponentHref(result)
              const opponent = result.opponent?.name ?? t("unknown")
              const named = subject === "clan" || result.side.type === "team"

              return (
                <li
                  key={`${result.fight.value}-${result.side.competitor?.value}`}
                  className="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 py-2"
                >
                  <div className="flex min-w-0 items-center gap-2">
                    <Badge variant={OUTCOME_VARIANT[result.outcome]}>
                      {t(`outcome.${result.outcome}`)}
                    </Badge>
                    <span className="flex min-w-0 items-center gap-1.5 text-sm">
                      {named && (
                        <>
                          {subject === "player" && (
                            <ClanTag
                              tag={result.side.tag}
                              dissolvedLabel={
                                result.side.clanDissolved
                                  ? t("dissolved")
                                  : undefined
                              }
                            />
                          )}
                          <span className="truncate font-medium">
                            {result.side.name ?? t("unknown")}
                          </span>
                        </>
                      )}
                      <span className="shrink-0">{t("against")}</span>
                      <ClanTag
                        tag={result.opponent?.tag}
                        dissolvedLabel={
                          result.opponent?.clanDissolved
                            ? t("dissolved")
                            : undefined
                        }
                      />
                      {href ? (
                        <Link
                          href={href}
                          className="truncate font-medium underline-offset-4 hover:underline"
                        >
                          {opponent}
                        </Link>
                      ) : (
                        <span className="truncate font-medium">{opponent}</span>
                      )}
                    </span>
                  </div>
                  <div className="flex items-center gap-2">
                    <span className="font-mono text-sm tabular-nums">
                      {result.side.score ?? 0} – {result.opponent?.score ?? 0}
                    </span>
                    <Badge variant="outline">
                      {formatLabel(result.teamSize)}
                    </Badge>
                    {result.tournament && (
                      <Badge variant="secondary">
                        <TrophyIcon data-icon="inline-start" />
                        {t("tournament")}
                      </Badge>
                    )}
                    {result.settledAt && (
                      <time
                        dateTime={result.settledAt}
                        className="text-xs text-muted-foreground"
                      >
                        {format.dateTime(new Date(result.settledAt), {
                          dateStyle: "medium",
                        })}
                      </time>
                    )}
                  </div>
                </li>
              )
            })}
          </ul>
        )}
        {history.ok && history.data.total > items.length && (
          <p className="text-xs text-muted-foreground">
            {t("latest", { shown: items.length, total: history.data.total })}
          </p>
        )}
      </CardContent>
    </Card>
  )
}
