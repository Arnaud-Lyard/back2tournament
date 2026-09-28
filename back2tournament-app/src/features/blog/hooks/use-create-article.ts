"use client"

import { useMutation } from "@tanstack/react-query"
import { createArticle } from "../api/blog.mutations"

export function useCreateArticle() {
  return useMutation({ mutationFn: createArticle })
}
