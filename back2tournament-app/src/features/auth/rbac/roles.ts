import type { AuthPermission, UserRole } from "./permissions"

export const ROLE_PERMISSIONS = {
  user: [],
  editor: [
    "backoffice:access",
    "article:create",
    "article:edit",
    "article:publish",
    "comment:moderate",
    "fight:record-result",
  ],
  admin: [
    "backoffice:access",
    "article:create",
    "article:edit",
    "article:delete",
    "article:publish",
    "category:manage",
    "comment:moderate",
    "game:manage",
    "team:manage",
    "player:manage",
    "fight:manage",
    "fight:record-result",
    "admin:access",
  ],
} as const satisfies Record<UserRole, readonly AuthPermission[]>

export const ROLE_PRECEDENCE: readonly UserRole[] = ["admin", "editor", "user"]

export function getPermissionsForRole(role: UserRole): AuthPermission[] {
  return [...ROLE_PERMISSIONS[role]]
}
