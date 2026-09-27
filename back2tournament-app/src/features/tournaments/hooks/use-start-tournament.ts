"use client"

import { useMutation } from "@tanstack/react-query"
import { startTournament } from "../api/tournaments.mutations"

/** The organizer closes registrations and draws the bracket. */
export function useStartTournament() {
  return useMutation({ mutationFn: startTournament })
}
