"use client"

import { useMutation } from "@tanstack/react-query"
import { createTeam } from "../api/teams.mutations"

/** The clan leader fields a lineup in one format. */
export function useCreateTeam() {
  return useMutation({ mutationFn: createTeam })
}
