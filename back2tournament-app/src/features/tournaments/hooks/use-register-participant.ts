"use client"

import { useMutation } from "@tanstack/react-query"
import { registerParticipant } from "../api/tournaments.mutations"

export function useRegisterParticipant() {
  return useMutation({ mutationFn: registerParticipant })
}
