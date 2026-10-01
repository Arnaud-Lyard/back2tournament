"use client"

import { useMutation } from "@tanstack/react-query"
import { dissolveClan } from "../api/clans.mutations"

export function useDissolveClan() {
  return useMutation({ mutationFn: dissolveClan })
}
