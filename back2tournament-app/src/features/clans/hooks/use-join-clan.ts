"use client"

import { useMutation } from "@tanstack/react-query"
import { joinClan } from "../api/clans.mutations"

export function useJoinClan() {
  return useMutation({ mutationFn: joinClan })
}
