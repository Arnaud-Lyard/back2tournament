"use client"

import { useMutation } from "@tanstack/react-query"
import { createGame } from "../api/games.mutations"

export function useCreateGame() {
  return useMutation({ mutationFn: createGame })
}
