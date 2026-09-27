import { TrophyIcon } from "lucide-react"
import Link from "next/link"
import { getFormatter, getTranslations } from "next-intl/server"
import { Badge } from "@/components/ui/badge"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { formatLabel } from "@/features/fights/lib/challenge"
import type { FightStatusFilter, FightSummary } from "@/features/fights/types"
import type { Game } from "@/features/games/types"
import { listHref } from "@/libs/list-params"
import { ListCard } from "../list-card"
import { FightStatusBadge } from "./fight-status-badge"

interface FightsListProps {
  fights: FightSummary[]
  games: Game[]
  status: FightStatusFilter
  /** The filters of the page, kept when a fight is opened. */
  params: Record<string, string | number | undefined>
  selectedId?: string
}

export async function FightsList({
  fights,
  games,
  status,
  params,
  selectedId,
}: FightsListProps) {
  const [t, format] = await Promise.all([
    getTranslations("backoffice.fights"),
    getFormatter(),
  ])
  const gameTitles = new Map(games.map((game) => [game.id?.value, game.title]))

  return (
    <ListCard
      title={t("list.title")}
      description={t(`list.description.${status}`)}
      count={fights.length}
      showCount={false}
      emptyTitle={t("list.emptyTitle")}
      emptyDescription={t("list.emptyDescription")}
    >
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>{t("list.columns.match")}</TableHead>
            <TableHead className="text-right">
              {t("list.columns.score")}
            </TableHead>
            <TableHead>{t("list.columns.game")}</TableHead>
            <TableHead>{t("list.columns.status")}</TableHead>
            <TableHead>{t("list.columns.updatedAt")}</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {fights.map((fight) => {
            const id = fight.id?.value
            if (!id) return null
            const [one, two] = fight.sides ?? []

            return (
              <TableRow
                key={id}
                data-state={id === selectedId ? "selected" : undefined}
              >
                <TableCell className="max-w-72">
                  <Link
                    href={listHref("/backoffice/fights", {
                      ...params,
                      fightId: id,
                    })}
                    scroll={false}
                    className="flex min-w-0 flex-wrap gap-x-1 font-medium underline-offset-4 hover:underline"
                  >
                    <span className="truncate">
                      {one?.name ?? t("unknownSide")}
                    </span>
                    <span className="text-muted-foreground">
                      {t("list.versus")}
                    </span>
                    <span className="truncate">
                      {two?.name ?? t("unknownSide")}
                    </span>
                  </Link>
                </TableCell>
                <TableCell className="text-right font-mono whitespace-nowrap tabular-nums">
                  {fight.status === "pending"
                    ? "—"
                    : `${one?.score ?? 0} – ${two?.score ?? 0}`}
                </TableCell>
                <TableCell>
                  <span className="flex flex-wrap items-center gap-1">
                    <span className="truncate">
                      {gameTitles.get(fight.game?.value)}
                    </span>
                    <Badge variant="outline">
                      {formatLabel(fight.teamSize)}
                    </Badge>
                    {fight.tournament && (
                      <Badge variant="secondary" title={t("list.tournament")}>
                        <TrophyIcon />
                      </Badge>
                    )}
                  </span>
                </TableCell>
                <TableCell>
                  <FightStatusBadge fight={fight} />
                </TableCell>
                <TableCell className="whitespace-nowrap">
                  {fight.updatedAt
                    ? format.dateTime(new Date(fight.updatedAt), {
                        dateStyle: "short",
                        timeStyle: "short",
                      })
                    : null}
                </TableCell>
              </TableRow>
            )
          })}
        </TableBody>
      </Table>
    </ListCard>
  )
}
