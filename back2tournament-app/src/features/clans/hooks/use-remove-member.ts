"use client"

import { useMutation } from "@tanstack/react-query"
import { removeMember } from "../api/clans.mutations"

export function useRemoveMember() {
  return useMutation({ mutationFn: removeMember })
}
