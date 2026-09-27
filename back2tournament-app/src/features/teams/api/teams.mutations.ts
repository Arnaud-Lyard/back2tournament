import { fetchJson } from "@/libs/api/fetch-json"
import type { Team } from "@/features/clans/types"
import type { CreateTeamInput } from "../schemas/create-team.schema"

export function createTeam(input: CreateTeamInput): Promise<Team> {
  return fetchJson<Team>("/api/teams", { method: "POST", body: input })
}

export function disbandTeam(teamId: string): Promise<Team> {
  return fetchJson<Team>(`/api/teams/${encodeURIComponent(teamId)}`, {
    method: "DELETE",
  })
}
