import { TrophyIcon } from "lucide-react"
import { getTranslations } from "next-intl/server"
import { ClanTag } from "@/components/clan-tag"
import { bracketRounds, roundName } from "@/features/tournaments/lib/bracket"
import type { TournamentMatchup } from "@/features/tournaments/types"
import { cn } from "@/libs/utils"

type Side = NonNullable<NonNullable<TournamentMatchup["sides"]>[number]>

/** The bracket, one column per round, the final on the right. */
export async function Bracket({
  matchups,
  rounds,
}: {
  matchups: TournamentMatchup[]
  rounds: number
}) {
  const t = await getTranslations("tournaments.bracket")

  return (
    <div className="overflow-x-auto pb-2">
      <ol className="flex gap-4">
        {bracketRounds(matchups).map((round, index) => {
          const name = roundName(index + 1, rounds)

          return (
            <li key={index} className="flex min-w-44 flex-1 flex-col gap-3">
              <h3 className="text-sm font-medium text-muted-foreground">
                {name.key === "roundOf"
                  ? t("roundOf", { count: name.count })
                  : t(name.key)}
              </h3>
              <ol className="flex flex-1 flex-col justify-around gap-3">
                {round.map((matchup) => (
                  <li
                    key={matchup.id?.value}
                    className="flex flex-col divide-y rounded-lg border bg-card text-sm"
                  >
                    {[0, 1].map((slot) => (
                      <SideRow
                        key={slot}
                        side={matchup.sides?.[slot] ?? null}
                        won={
                          !!matchup.winner &&
                          matchup.sides?.[slot]?.competitor?.value ===
                            matchup.winner.value
                        }
                        placeholder={index === 0 ? t("bye") : t("toBeDecided")}
                      />
                    ))}
                    {matchup.fight && !matchup.winner && (
                      <p className="px-3 py-1 text-xs text-muted-foreground">
                        {t("underWay")}
                      </p>
                    )}
                  </li>
                ))}
              </ol>
            </li>
          )
        })}
      </ol>
    </div>
  )
}

function SideRow({
  side,
  won,
  placeholder,
}: {
  side: Side | null
  won: boolean
  placeholder: string
}) {
  return (
    <div
      className={cn(
        "flex items-center justify-between gap-2 px-3 py-2",
        won && "font-semibold",
        !side && "text-muted-foreground italic"
      )}
    >
      <span className="flex min-w-0 items-center gap-1.5">
        {side && <ClanTag tag={side.tag} />}
        <span className="truncate">
          {side ? (side.name ?? "?") : placeholder}
        </span>
      </span>
      {won && <TrophyIcon className="size-4 shrink-0 text-primary" />}
    </div>
  )
}
