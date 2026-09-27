"use client"

import { useMutation } from "@tanstack/react-query"
import { cancelTournament } from "../api/tournaments.mutations"

/** The organizer cancels the tournament. */
export function useCancelTournament() {
  return useMutation({ mutationFn: cancelTournament })
}
