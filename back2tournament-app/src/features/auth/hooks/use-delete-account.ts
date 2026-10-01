"use client"

import { useMutation } from "@tanstack/react-query"
import { deleteAccount } from "../api/account.mutations"

export function useDeleteAccount() {
  return useMutation({ mutationFn: deleteAccount })
}
