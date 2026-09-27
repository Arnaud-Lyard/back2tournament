"use client"

import { useMutation } from "@tanstack/react-query"
import { removeMember } from "../api/clans.mutations"

/** Ends a place in a clan: leave, decline, withdraw an invitation or let go. */
export function useRemoveMember() {
  return useMutation({ mutationFn: removeMember })
}
