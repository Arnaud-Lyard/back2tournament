"use client"

import { useMutation } from "@tanstack/react-query"
import { disbandTeam } from "../api/teams.mutations"

export function useDisbandTeam() {
  return useMutation({ mutationFn: disbandTeam })
}
