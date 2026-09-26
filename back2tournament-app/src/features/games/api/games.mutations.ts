import { fetchJson } from "@/libs/api/fetch-json"
import type { CreateGameInput } from "../schemas/create-game.schema"
import type { CreatedGame } from "../types"

export function createGame(input: CreateGameInput): Promise<CreatedGame> {
  return fetchJson<CreatedGame>("/api/games", { method: "POST", body: input })
}
