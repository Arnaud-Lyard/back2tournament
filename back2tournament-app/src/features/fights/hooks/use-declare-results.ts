"use client"

import { useMutation } from "@tanstack/react-query"
import { declareResults } from "../api/fights.mutations"

export function useDeclareResults() {
  return useMutation({ mutationFn: declareResults })
}
