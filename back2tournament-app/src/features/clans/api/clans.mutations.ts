import { fetchJson } from "@/libs/api/fetch-json"
import type { CreateClanInput } from "../schemas/create-clan.schema"
import type { InvitePlayerInput } from "../schemas/invite-player.schema"
import type { Clan, ClanMember } from "../types"

export interface InvitePlayerVariables extends InvitePlayerInput {
  clanId: string
}

export interface RemoveMemberVariables {
  clanId: string
  playerId: string
}

export function createClan(input: CreateClanInput): Promise<Clan> {
  return fetchJson<Clan>("/api/clans", { method: "POST", body: input })
}

export function invitePlayer({
  clanId,
  player,
}: InvitePlayerVariables): Promise<ClanMember> {
  return fetchJson<ClanMember>(
    `/api/clans/${encodeURIComponent(clanId)}/invitations`,
    { method: "POST", body: { player } }
  )
}

/** Accepts the invitation the caller's profile holds in this clan. */
export function joinClan(clanId: string): Promise<ClanMember> {
  return fetchJson<ClanMember>(
    `/api/clans/${encodeURIComponent(clanId)}/members`,
    { method: "POST" }
  )
}

/** Leaving, declining, withdrawing an invitation or letting a member go. */
export function removeMember({
  clanId,
  playerId,
}: RemoveMemberVariables): Promise<ClanMember> {
  return fetchJson<ClanMember>(
    `/api/clans/${encodeURIComponent(clanId)}/members/${encodeURIComponent(playerId)}`,
    { method: "DELETE" }
  )
}
