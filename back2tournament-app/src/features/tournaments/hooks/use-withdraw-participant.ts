"use client"

import { useMutation } from "@tanstack/react-query"
import { withdrawParticipant } from "../api/tournaments.mutations"

export function useWithdrawParticipant() {
  return useMutation({ mutationFn: withdrawParticipant })
}
