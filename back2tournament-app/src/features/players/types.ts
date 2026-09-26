import type { components, paths } from "@/libs/api/schema"

export type Player = components["schemas"]["Player"]

export type CreatedPlayer =
  paths["/api/players/"]["post"]["responses"][200]["content"]["application/json"]

export type UpdatedPlayer =
  paths["/api/players/{id}"]["patch"]["responses"][200]["content"]["application/json"]

export type DeletedPlayer =
  paths["/api/players/{id}"]["delete"]["responses"][200]["content"]["application/json"]

export const PLAYERS_PER_PAGE = 12
