import type { Game } from "../types"

/** The game a `?gameId=` names, when it is one of the listed games. */
export function findGame(
  games: readonly Game[],
  gameId: string | undefined
): Game | undefined {
  return gameId ? games.find((game) => game.id?.value === gameId) : undefined
}
