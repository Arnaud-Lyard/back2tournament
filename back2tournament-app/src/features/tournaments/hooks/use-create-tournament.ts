"use client"

import { useMutation } from "@tanstack/react-query"
import { createTournament } from "../api/tournaments.mutations"

/** Organizes a tournament; the caller is its organizer. */
export function useCreateTournament() {
  return useMutation({ mutationFn: createTournament })
}
