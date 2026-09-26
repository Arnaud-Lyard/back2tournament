import { fetchJson } from "@/libs/api/fetch-json"
import type { CreateTournamentInput } from "../schemas/create-tournament.schema"
import type { RegisterParticipantInput } from "../schemas/register-participant.schema"
import type { TournamentDetail, TournamentParticipant } from "../types"

export type RegisterParticipantVariables = RegisterParticipantInput & {
  tournamentId: string
}

export interface WithdrawParticipantVariables {
  tournamentId: string
  participantId: string
}

function tournamentPath(tournamentId: string): string {
  return `/api/tournaments/${encodeURIComponent(tournamentId)}`
}

export function createTournament(
  input: CreateTournamentInput
): Promise<TournamentDetail> {
  return fetchJson<TournamentDetail>("/api/tournaments", {
    method: "POST",
    body: input,
  })
}

export function registerParticipant({
  tournamentId,
  ...input
}: RegisterParticipantVariables): Promise<TournamentParticipant> {
  return fetchJson<TournamentParticipant>(
    `${tournamentPath(tournamentId)}/participants`,
    {
      method: "POST",
      body: input,
    }
  )
}

export function withdrawParticipant({
  tournamentId,
  participantId,
}: WithdrawParticipantVariables): Promise<TournamentParticipant> {
  return fetchJson<TournamentParticipant>(
    `${tournamentPath(tournamentId)}/participants/${encodeURIComponent(participantId)}`,
    { method: "DELETE" }
  )
}

export function startTournament(
  tournamentId: string
): Promise<TournamentDetail> {
  return fetchJson<TournamentDetail>(`${tournamentPath(tournamentId)}/start`, {
    method: "POST",
  })
}

export function cancelTournament(
  tournamentId: string
): Promise<TournamentDetail> {
  return fetchJson<TournamentDetail>(`${tournamentPath(tournamentId)}/cancel`, {
    method: "POST",
  })
}
