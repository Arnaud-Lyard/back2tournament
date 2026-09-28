"use client"

import { useMutation } from "@tanstack/react-query"
import { changeFightStatus } from "../api/fights.mutations"

export function useChangeFightStatus() {
  return useMutation({ mutationFn: changeFightStatus })
}
