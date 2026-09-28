import { mapSymfonyRoles } from "../rbac/map-symfony-roles"
import type { AuthUser, CurrentUser } from "../types"

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
