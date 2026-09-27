import { ArrowRightIcon } from "lucide-react"
import Link from "next/link"
import { getTranslations } from "next-intl/server"
import { ClanTag } from "@/components/clan-tag"
import { PlayerAvatar } from "@/components/player-avatar"
import { Card, CardDescription, CardTitle } from "@/components/ui/card"
import type { GamePlayer } from "@/features/players/types"

export async function PlayerCard({
  player,
  gameId,
}: {
  player: GamePlayer
  gameId: string
}) {
  const id = player.id?.value
  if (!id) return null

  const t = await getTranslations("games.players")
  const battletag = player.battletag ?? ""

  return (
    <li>
      <Card className="group h-full gap-0 p-0 transition-colors hover:border-primary/50">
        <Link
          href={`/games/${encodeURIComponent(gameId)}/players/${encodeURIComponent(id)}`}
          className="flex h-full items-center gap-3 rounded-[inherit] p-4 outline-none focus-visible:ring-3 focus-visible:ring-ring/50"
        >
          <PlayerAvatar battletag={battletag} size="lg" />
          <div className="flex min-w-0 flex-col gap-1">
            <div className="flex min-w-0 items-center gap-2">
              <ClanTag tag={player.clanTag} />
              <CardTitle className="truncate">{battletag}</CardTitle>
            </div>
            <CardDescription className="inline-flex items-center gap-1 text-primary">
              {t("view")}
              <ArrowRightIcon className="size-4 transition-transform group-hover:translate-x-0.5" />
            </CardDescription>
          </div>
        </Link>
      </Card>
    </li>
  )
}
