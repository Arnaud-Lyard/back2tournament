import type { ClanDetail, MyClan, Team } from "../types"

export function activeClanIn(
  myClans: readonly MyClan[],
  gameId: string
): MyClan | undefined {
  return myClans.find(
    (entry) =>
      entry.clan.game?.value === gameId && entry.membership.status === "active"
  )
}

export function teamsLedBy(
  clan: ClanDetail | undefined,
  playerId: string | undefined
): Team[] {
  if (!clan || !playerId) return []
  return (clan.teams ?? []).filter((team) => team.leader?.value === playerId)
}

export function teamFormats(
  teamSizes: readonly number[] | undefined
): number[] {
  return (teamSizes ?? []).filter((size) => size > 1)
}
