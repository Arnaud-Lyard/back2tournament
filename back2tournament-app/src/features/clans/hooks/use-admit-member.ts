"use client"

import { useMutation } from "@tanstack/react-query"
import { admitMember } from "../api/clans.mutations"

export function useAdmitMember() {
  return useMutation({ mutationFn: admitMember })
}
