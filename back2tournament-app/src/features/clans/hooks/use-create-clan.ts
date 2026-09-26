"use client"

import { useMutation } from "@tanstack/react-query"
import { createClan } from "../api/clans.mutations"

/** Founds a clan in a game; the caller's profile there leads it. */
export function useCreateClan() {
  return useMutation({ mutationFn: createClan })
}
