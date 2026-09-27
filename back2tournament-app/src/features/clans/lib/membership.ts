import type { ClanDetail, MyClan, Team } from "../types"

/** The caller's active membership in a game's clan, if they have one. */
export function activeClanIn(
  myClans: readonly MyClan[],
  gameId: string
): MyClan | undefined {
  return myClans.find(
    (entry) =>
      entry.clan.game?.value === gameId && entry.membership.status === "active"
  )
}

/** The teams of a clan a given player leads. */
export function teamsLedBy(
  clan: ClanDetail | undefined,
  playerId: string | undefined
): Team[] {
  if (!clan || !playerId) return []
  return (clan.teams ?? []).filter((team) => team.leader?.value === playerId)
}

/** Teams are fielded in the formats of the game above 1v1. */
export function teamFormats(
  teamSizes: readonly number[] | undefined
): number[] {
  return (teamSizes ?? []).filter((size) => size > 1)
}
