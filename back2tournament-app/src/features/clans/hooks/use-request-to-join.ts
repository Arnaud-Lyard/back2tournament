"use client"

import { useMutation } from "@tanstack/react-query"
import { requestToJoin } from "../api/clans.mutations"

export function useRequestToJoin() {
  return useMutation({ mutationFn: requestToJoin })
}
