import type { components, paths } from "@/libs/api/schema"

export type Game = components["schemas"]["Game"]

export type CreatedGame =
  paths["/api/admin/games/"]["post"]["responses"][200]["content"]["application/json"]
