"use client"

import { useMutation } from "@tanstack/react-query"
import { deletePlayer } from "../api/players.mutations"

export function useDeletePlayer() {
  return useMutation({ mutationFn: deletePlayer })
}
