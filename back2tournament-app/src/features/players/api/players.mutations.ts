import { fetchJson } from "@/libs/api/fetch-json"
import type { CreatePlayerInput } from "../schemas/create-player.schema"
import type { UpdatePlayerInput } from "../schemas/update-player.schema"
import type { CreatedPlayer, DeletedPlayer, UpdatedPlayer } from "../types"

export interface UpdatePlayerVariables extends UpdatePlayerInput {
  id: string
}

export function createPlayer(input: CreatePlayerInput): Promise<CreatedPlayer> {
  return fetchJson<CreatedPlayer>("/api/players", {
    method: "POST",
    body: input,
  })
}

export function updatePlayer({
  id,
  ...input
}: UpdatePlayerVariables): Promise<UpdatedPlayer> {
  return fetchJson<UpdatedPlayer>(`/api/players/${encodeURIComponent(id)}`, {
    method: "PATCH",
    body: input,
  })
}

export function deletePlayer(id: string): Promise<DeletedPlayer> {
  return fetchJson<DeletedPlayer>(`/api/players/${encodeURIComponent(id)}`, {
    method: "DELETE",
  })
}
