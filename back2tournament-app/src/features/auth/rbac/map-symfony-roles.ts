import type { AuthPermission, UserRole } from "./permissions"
import { getPermissionsForRole, ROLE_PRECEDENCE } from "./roles"

const SYMFONY_ROLE_MAP: Record<string, UserRole> = {
  ROLE_ADMIN: "admin",
  ROLE_EDITOR: "editor",
  ROLE_USER: "user",
}

/**
 * Maps the Symfony backend's raw `roles: string[]` (ROLE_* strings) onto this
 * app's RBAC model. A user can hold several ROLE_* strings at once; the
 * highest-privilege one (per ROLE_PRECEDENCE) wins. Unknown strings are
 * dropped; empty or fully-unrecognized input defaults to "user".
 */
export function mapSymfonyRoles(rawRoles: readonly string[]): {
  role: UserRole
  permissions: AuthPermission[]
} {
  const mapped = new Set(
    rawRoles
      .map((raw) => SYMFONY_ROLE_MAP[raw])
      .filter((role): role is UserRole => !!role)
  )

  const role =
    ROLE_PRECEDENCE.find((candidate) => mapped.has(candidate)) ?? "user"

  return { role, permissions: getPermissionsForRole(role) }
}
