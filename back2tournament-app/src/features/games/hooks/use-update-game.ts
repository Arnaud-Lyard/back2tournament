"use client"

import { useMutation } from "@tanstack/react-query"
import { updateGame } from "../api/games.mutations"

export function useUpdateGame() {
  return useMutation({ mutationFn: updateGame })
}
