import type { components, paths } from "@/libs/api/schema"

export type GamePlayer = components["schemas"]["GamePlayer"]

export type CreatedPlayer =
  paths["/api/user/players/"]["post"]["responses"][200]["content"]["application/json"]

export type UpdatedPlayer =
  paths["/api/user/players/{id}"]["patch"]["responses"][200]["content"]["application/json"]

export type DeletedPlayer =
  paths["/api/user/players/{id}"]["delete"]["responses"][200]["content"]["application/json"]

export const PLAYERS_PER_PAGE = 12
