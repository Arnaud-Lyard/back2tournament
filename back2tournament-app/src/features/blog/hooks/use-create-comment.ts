"use client"

import { useMutation } from "@tanstack/react-query"
import { createComment } from "../api/blog.mutations"

export function useCreateComment() {
  return useMutation({ mutationFn: createComment })
}
