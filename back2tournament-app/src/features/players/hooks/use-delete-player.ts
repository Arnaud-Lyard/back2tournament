"use client"

import { useMutation } from "@tanstack/react-query"
import { deletePlayer } from "../api/players.mutations"

/** Deletes one of the caller's own player profiles, as long as it never fought. */
export function useDeletePlayer() {
  return useMutation({ mutationFn: deletePlayer })
}
