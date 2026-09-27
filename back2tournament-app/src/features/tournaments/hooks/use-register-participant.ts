"use client"

import { useMutation } from "@tanstack/react-query"
import { registerParticipant } from "../api/tournaments.mutations"

/** Registers the caller's profile, or a team they lead. */
export function useRegisterParticipant() {
  return useMutation({ mutationFn: registerParticipant })
}
