import { listHref } from "@/libs/list-params"
import type { RankedSubject, RankingView } from "../types"

/** The ranking a page shows: the players', unless the clans' is asked for. */
export function readRankingView(
  value: string | string[] | undefined
): RankingView {
  const raw = Array.isArray(value) ? value[0] : value
  return raw === "clans" ? "clans" : "players"
}

/**
 * The format a page ranks, as the number of players per side: the one asked
 * for when the game is played in it, its smallest format otherwise.
 * Undefined when the game's formats are unknown: the API then picks.
 */
export function readRankingSize(
  value: string | string[] | undefined,
  formats: readonly number[]
): number | undefined {
  const raw = (Array.isArray(value) ? value[0] : value)?.trim()
  const asked = raw && /^\d+$/.test(raw) ? Number.parseInt(raw, 10) : undefined

  if (asked !== undefined && formats.includes(asked)) return asked
  return formats.length > 0 ? Math.min(...formats) : undefined
}

/** The page of a ranked player profile or clan, in its game. */
export function rankedSubjectHref(
  gameId: string,
  subject: RankedSubject | undefined
): string | null {
  const id = subject?.id?.value
  if (!id) return null

  const section = subject.type === "clan" ? "clans" : "players"
  return `/games/${encodeURIComponent(gameId)}/${section}/${encodeURIComponent(id)}`
}

/**
 * Where a game's ranking is, on the view that ranks this kind of subject and
 * in a format; without one, the smallest format of the game.
 */
export function rankingHref(
  gameId: string,
  view: RankingView,
  size?: number
): string {
  return listHref(`/games/${encodeURIComponent(gameId)}/rankings`, {
    view: view === "clans" ? "clans" : undefined,
    size,
  })
}
