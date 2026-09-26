"use client"

import { useMutation } from "@tanstack/react-query"
import { createFight } from "../api/fights.mutations"

export function useCreateFight() {
  return useMutation({ mutationFn: createFight })
}
