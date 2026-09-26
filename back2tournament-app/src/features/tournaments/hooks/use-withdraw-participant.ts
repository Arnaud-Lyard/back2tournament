"use client"

import { useMutation } from "@tanstack/react-query"
import { withdrawParticipant } from "../api/tournaments.mutations"

/** Withdraws a registration before the tournament starts. */
export function useWithdrawParticipant() {
  return useMutation({ mutationFn: withdrawParticipant })
}
