"use client"

import { useMutation } from "@tanstack/react-query"
import { createCategory } from "../api/blog.mutations"

export function useCreateCategory() {
  return useMutation({ mutationFn: createCategory })
}
