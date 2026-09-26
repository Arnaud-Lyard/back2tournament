"use client"

import { useMutation } from "@tanstack/react-query"
import { updateGame } from "../api/games.mutations"

/** Admin only: changes the formats a game is played in. */
export function useUpdateGame() {
  return useMutation({ mutationFn: updateGame })
}
