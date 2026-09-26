"use client"

import { useMutation } from "@tanstack/react-query"
import { createCategory } from "../api/blog.mutations"

/** Admin only: adds a category articles can be filed under, by slug. */
export function useCreateCategory() {
  return useMutation({ mutationFn: createCategory })
}
