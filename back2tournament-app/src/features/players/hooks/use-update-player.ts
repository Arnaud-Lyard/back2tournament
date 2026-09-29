"use client"

import { useMutation } from "@tanstack/react-query"
import { updatePlayer } from "../api/players.mutations"

export function useUpdatePlayer() {
  return useMutation({ mutationFn: updatePlayer })
}
