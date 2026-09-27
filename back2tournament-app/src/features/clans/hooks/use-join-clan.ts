"use client"

import { useMutation } from "@tanstack/react-query"
import { joinClan } from "../api/clans.mutations"

/** Accepts an invitation: the caller becomes a member. */
export function useJoinClan() {
  return useMutation({ mutationFn: joinClan })
}
