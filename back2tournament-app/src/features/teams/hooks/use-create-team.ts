"use client"

import { useMutation } from "@tanstack/react-query"
import { createTeam } from "../api/teams.mutations"

export function useCreateTeam() {
  return useMutation({ mutationFn: createTeam })
}
