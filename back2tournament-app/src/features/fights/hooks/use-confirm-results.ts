"use client"

import { useMutation } from "@tanstack/react-query"
import { confirmResults } from "../api/fights.mutations"

export function useConfirmResults() {
  return useMutation({ mutationFn: confirmResults })
}
