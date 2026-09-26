"use client"

import { useMutation } from "@tanstack/react-query"
import { declareResults } from "../api/fights.mutations"

/** Declares the scores of a fight, from the caller's side, or corrects them. */
export function useDeclareResults() {
  return useMutation({ mutationFn: declareResults })
}
