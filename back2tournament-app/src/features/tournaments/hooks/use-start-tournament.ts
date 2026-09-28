"use client"

import { useMutation } from "@tanstack/react-query"
import { startTournament } from "../api/tournaments.mutations"

export function useStartTournament() {
  return useMutation({ mutationFn: startTournament })
}
