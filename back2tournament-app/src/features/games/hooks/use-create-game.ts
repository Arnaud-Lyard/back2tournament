"use client"

import { useMutation } from "@tanstack/react-query"
import { createGame } from "../api/games.mutations"

/** Admin only: adds a game players can then create a profile in. */
export function useCreateGame() {
  return useMutation({ mutationFn: createGame })
}
