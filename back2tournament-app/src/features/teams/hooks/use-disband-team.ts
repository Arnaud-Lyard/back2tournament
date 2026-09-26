"use client"

import { useMutation } from "@tanstack/react-query"
import { disbandTeam } from "../api/teams.mutations"

/** The clan leader disbands a team that never competed. */
export function useDisbandTeam() {
  return useMutation({ mutationFn: disbandTeam })
}
