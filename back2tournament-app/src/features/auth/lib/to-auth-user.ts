import { mapSymfonyRoles } from "../rbac/map-symfony-roles"
import type { AuthUser, CurrentUser } from "../types"

/**
 * Maps GET /api/users/me onto the app's AuthUser. Players arrive as a list
 * and are indexed by game here, once: a user holds at most one per game.
 */
export function toAuthUser(me: CurrentUser): AuthUser {
  return {
    id: me.id,
    username: me.username,
    ...mapSymfonyRoles(me.roles),
    verified: me.verified,
    avatar: me.avatar ?? null,
    playersByGame: Object.fromEntries(
      me.players.map(({ game, ...player }) => [game, player])
    ),
  }
}

/**
 * The identity a JWT alone carries, for when the backend cannot be asked:
 * no picture nor player profile is known then.
 */
export function authUserFromJwt(
  username: string,
  roles: readonly string[]
): AuthUser {
  return {
    username,
    ...mapSymfonyRoles(roles),
    verified: true,
    avatar: null,
    playersByGame: {},
  }
}
