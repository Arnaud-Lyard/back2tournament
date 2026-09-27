import type { RankedSubject, RankingView } from "../types"

/** The ranking a page shows: the players', unless the clans' is asked for. */
export function readRankingView(
  value: string | string[] | undefined
): RankingView {
  const raw = Array.isArray(value) ? value[0] : value
  return raw === "clans" ? "clans" : "players"
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

/** Where a game's ranking is, on the view that ranks this kind of subject. */
export function rankingHref(gameId: string, view: RankingView): string {
  const base = `/games/${encodeURIComponent(gameId)}/rankings`
  return view === "clans" ? `${base}?view=clans` : base
}
