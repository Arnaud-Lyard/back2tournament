import { listHref } from "@/libs/list-params"
import type { RankedSubject, RankingView } from "../types"

export function readRankingView(
  value: string | string[] | undefined
): RankingView {
  const raw = Array.isArray(value) ? value[0] : value
  return raw === "clans" ? "clans" : "players"
}

export function readRankingSize(
  value: string | string[] | undefined,
  formats: readonly number[]
): number | undefined {
  const raw = (Array.isArray(value) ? value[0] : value)?.trim()
  const asked = raw && /^\d+$/.test(raw) ? Number.parseInt(raw, 10) : undefined

  if (asked !== undefined && formats.includes(asked)) return asked
  return formats.length > 0 ? Math.min(...formats) : undefined
}

export function rankedSubjectHref(
  gameId: string,
  subject: RankedSubject | undefined
): string | null {
  const id = subject?.id?.value
  if (!id) return null

  const section = subject.type === "clan" ? "clans" : "players"
  return `/games/${encodeURIComponent(gameId)}/${section}/${encodeURIComponent(id)}`
}

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
