"use client"

import { useMutation } from "@tanstack/react-query"
import { cancelTournament } from "../api/tournaments.mutations"

export function useCancelTournament() {
  return useMutation({ mutationFn: cancelTournament })
}
