"use client"

import { useMutation } from "@tanstack/react-query"
import { updateArticle } from "../api/blog.mutations"

export function useUpdateArticle() {
  return useMutation({ mutationFn: updateArticle })
}
