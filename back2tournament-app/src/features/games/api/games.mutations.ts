import { fetchJson } from "@/libs/api/fetch-json"
import type { CreateGameInput } from "../schemas/create-game.schema"
import type { UpdateGameInput } from "../schemas/update-game.schema"
import type { CreatedGame, Game } from "../types"

export interface UpdateGameVariables extends UpdateGameInput {
  id: string
}

export function createGame(input: CreateGameInput): Promise<CreatedGame> {
  return fetchJson<CreatedGame>("/api/games", { method: "POST", body: input })
}

export function updateGame({
  id,
  ...input
}: UpdateGameVariables): Promise<Game> {
  return fetchJson<Game>(`/api/games/${encodeURIComponent(id)}`, {
    method: "PATCH",
    body: input,
  })
}
