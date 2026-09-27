import { fetchJson } from "@/libs/api/fetch-json"
import type { CreateFightInput } from "../schemas/create-fight.schema"
import type { DeclareResultsInput } from "../schemas/declare-results.schema"
import type { CreatedFight, FightSummary } from "../types"

export interface DeclareResultsVariables extends DeclareResultsInput {
  fightId: string
}

export function createFight(input: CreateFightInput): Promise<CreatedFight> {
  return fetchJson<CreatedFight>("/api/fights", {
    method: "POST",
    body: input,
  })
}

export function declareResults({
  fightId,
  ...input
}: DeclareResultsVariables): Promise<FightSummary> {
  return fetchJson<FightSummary>(
    `/api/fights/${encodeURIComponent(fightId)}/results`,
    { method: "PATCH", body: input }
  )
}

export function confirmResults(fightId: string): Promise<FightSummary> {
  return fetchJson<FightSummary>(
    `/api/fights/${encodeURIComponent(fightId)}/confirmation`,
    { method: "POST" }
  )
}
