import { TrophyIcon } from "lucide-react"
import { getTranslations } from "next-intl/server"
import { CopyButton } from "@/components/copy-button"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Badge } from "@/components/ui/badge"
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { Separator } from "@/components/ui/separator"
import { formatLabel } from "@/features/fights/lib/challenge"
import type { FightSummary } from "@/features/fights/types"
import type { Loaded } from "@/libs/api/load"
import { ArbitrationForm } from "./arbitration-form"
import { FightStatusBadge } from "./fight-status-badge"

interface FightPanelProps {
  fightId: string
  fight: Loaded<FightSummary>
  gameTitle?: string
}

/** One fight, as a dispute reaches it: who declared what, and what to do. */
export async function FightPanel({
  fightId,
  fight: loaded,
  gameTitle,
}: FightPanelProps) {
  const t = await getTranslations("backoffice.fights")

  if (!loaded.ok) {
    const reason = loaded.status === 404 ? "notFound" : "loadError"
    return (
      <Alert variant="destructive">
        <AlertTitle>{t(`panel.${reason}.title`)}</AlertTitle>
        <AlertDescription>{t(`panel.${reason}.description`)}</AlertDescription>
      </Alert>
    )
  }

  const fight = loaded.data
  const sides = fight.sides ?? []
  const [one, two] = sides
  const declaring = sides.find(
    (side) => side.competitor?.value === fight.declaredBy?.value
  )

  return (
    <Card>
      <CardHeader>
        <CardTitle>
          {t("panel.title", {
            one: one?.name ?? t("unknownSide"),
            two: two?.name ?? t("unknownSide"),
          })}
        </CardTitle>
        <CardDescription className="flex flex-wrap items-center gap-2">
          {gameTitle}
          <Badge variant="outline">{formatLabel(fight.teamSize)}</Badge>
          {fight.tournament && (
            <Badge variant="secondary">
              <TrophyIcon data-icon="inline-start" />
              {t("list.tournament")}
            </Badge>
          )}
        </CardDescription>
        <CardAction>
          <FightStatusBadge fight={fight} />
        </CardAction>
      </CardHeader>
      <CardContent className="flex flex-col gap-4">
        <dl className="grid grid-cols-[minmax(0,1fr)_auto] gap-x-4 gap-y-1">
          {sides.map((side, index) => (
            <div key={side.competitor?.value ?? index} className="contents">
              <dt className="truncate">
                {side.name ?? t("unknownSide")}
                {side.competitor?.value === fight.declaredBy?.value && (
                  <span className="text-xs text-muted-foreground">
                    {" "}
                    {t("panel.declaring")}
                  </span>
                )}
              </dt>
              <dd className="text-right font-mono tabular-nums">
                {fight.status === "pending" ? "—" : side.score}
              </dd>
            </div>
          ))}
        </dl>
        <p className="text-sm text-muted-foreground">
          {fight.status === "finished"
            ? t("panel.finished")
            : declaring
              ? t("panel.declaredBy", {
                  name: declaring.name ?? t("unknownSide"),
                })
              : t("panel.noDeclaration")}
        </p>
        <div className="flex items-center gap-2 text-xs text-muted-foreground">
          <span>{t("panel.fightId")}</span>
          <code className="truncate">{fightId}</code>
          <CopyButton value={fightId} label={t("panel.copyId")} />
        </div>
        {fight.status !== "finished" && one && two && (
          <>
            <Separator />
            <ArbitrationForm
              key={fight.updatedAt}
              fightId={fightId}
              one={{
                competitor: one.competitor?.value ?? "",
                name: one.name ?? t("unknownSide"),
                score: one.score ?? 0,
              }}
              two={{
                competitor: two.competitor?.value ?? "",
                name: two.name ?? t("unknownSide"),
                score: two.score ?? 0,
              }}
              declared={fight.status === "reporting"}
              tournament={!!fight.tournament}
            />
          </>
        )}
      </CardContent>
    </Card>
  )
}
