"use client"

import { useMutation } from "@tanstack/react-query"
import { createPlayer } from "../api/players.mutations"

export function useCreatePlayer() {
  return useMutation({ mutationFn: createPlayer })
}
