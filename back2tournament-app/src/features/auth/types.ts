import type { components } from "@/libs/api/schema"
import type { AuthPermission, UserRole } from "./rbac/permissions"

export type { AuthPermission, UserRole } from "./rbac/permissions"

/** What the backend answers on GET /api/users/me. */
export type CurrentUser = components["schemas"]["CurrentUser"]

/** One of the caller's player profiles; its game is the key it is filed under. */
export interface PlayerProfile {
  id: string
  battletag: string
}

export interface AuthUser {
  username: string
  role: UserRole
  permissions: AuthPermission[]
  verified: boolean
  /** The caller's player profile in each game, keyed by game id. */
  playersByGame: Partial<Record<string, PlayerProfile>>
}
