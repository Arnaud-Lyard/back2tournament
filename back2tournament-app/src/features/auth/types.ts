import type { components } from "@/libs/api/schema"
import type { AuthPermission, UserRole } from "./rbac/permissions"

export type { AuthPermission, UserRole } from "./rbac/permissions"

export type CurrentUser = components["schemas"]["CurrentUser"]

export interface PlayerProfile {
  id: string
  battletag: string
}

export interface AuthUser {
  id?: string
  username: string
  role: UserRole
  permissions: AuthPermission[]
  verified: boolean
  avatar: string | null
  playersByGame: Partial<Record<string, PlayerProfile>>
}
