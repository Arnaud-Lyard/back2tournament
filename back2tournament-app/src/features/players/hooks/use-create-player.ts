"use client"

import { useMutation } from "@tanstack/react-query"
import { createPlayer } from "../api/players.mutations"

/** Creates the caller's own player profile in a game (one per game). */
export function useCreatePlayer() {
  return useMutation({ mutationFn: createPlayer })
}
