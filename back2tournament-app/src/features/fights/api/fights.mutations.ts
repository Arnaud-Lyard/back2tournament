import { fetchJson } from "@/libs/api/fetch-json"
import type { CreateFightInput } from "../schemas/create-fight.schema"
import type { CreatedFight } from "../types"

export function createFight(input: CreateFightInput): Promise<CreatedFight> {
  return fetchJson<CreatedFight>("/api/fights", {
    method: "POST",
    body: input,
  })
}
