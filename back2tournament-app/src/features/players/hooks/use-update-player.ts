"use client"

import { useMutation } from "@tanstack/react-query"
import { updatePlayer } from "../api/players.mutations"

/** Renames one of the caller's own player profiles. */
export function useUpdatePlayer() {
  return useMutation({ mutationFn: updatePlayer })
}
