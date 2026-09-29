"use client"

import { useMutation } from "@tanstack/react-query"
import { createTournament } from "../api/tournaments.mutations"

export function useCreateTournament() {
  return useMutation({ mutationFn: createTournament })
}
