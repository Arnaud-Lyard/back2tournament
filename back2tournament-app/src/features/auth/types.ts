import type { CurrentUser } from "@/libs/api/generated/endpoints.schemas"
import type { AuthPermission, UserRole } from "./rbac/permissions"

export type { AuthPermission, UserRole } from "./rbac/permissions"

export type { CurrentUser }

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
