"use client"

import { useMutation } from "@tanstack/react-query"
import { invitePlayer } from "../api/clans.mutations"

export function useInvitePlayer() {
  return useMutation({ mutationFn: invitePlayer })
}
