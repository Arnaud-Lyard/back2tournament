"use client"

import { useMutation } from "@tanstack/react-query"
import { changeFightStatus } from "../api/fights.mutations"

/** Admin only: settles a fight in dispute, or sets its declaration aside. */
export function useChangeFightStatus() {
  return useMutation({ mutationFn: changeFightStatus })
}
