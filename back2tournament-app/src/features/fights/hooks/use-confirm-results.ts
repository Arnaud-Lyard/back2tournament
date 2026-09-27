"use client"

import { useMutation } from "@tanstack/react-query"
import { confirmResults } from "../api/fights.mutations"

/** Agrees with the scores the other side declared: the fight is settled. */
export function useConfirmResults() {
  return useMutation({ mutationFn: confirmResults })
}
