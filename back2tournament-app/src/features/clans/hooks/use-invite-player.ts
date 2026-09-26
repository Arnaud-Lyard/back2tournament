"use client"

import { useMutation } from "@tanstack/react-query"
import { invitePlayer } from "../api/clans.mutations"

/** The clan leader invites a player of the game. */
export function useInvitePlayer() {
  return useMutation({ mutationFn: invitePlayer })
}
