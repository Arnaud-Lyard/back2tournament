import type { Game } from "../types"

export function findGame(
  games: readonly Game[],
  gameId: string | undefined
): Game | undefined {
  return gameId ? games.find((game) => game.id?.value === gameId) : undefined
}
