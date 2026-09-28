"use client"

import { useMutation } from "@tanstack/react-query"
import { createClan } from "../api/clans.mutations"

export function useCreateClan() {
  return useMutation({ mutationFn: createClan })
}
