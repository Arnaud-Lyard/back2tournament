import type { components, paths } from "@/libs/api/schema"

export type Game = components["schemas"]["Game"]

export type CreatedGame =
  paths["/api/games/"]["post"]["responses"][200]["content"]["application/json"]
