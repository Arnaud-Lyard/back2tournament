"use client"

import { useMutation } from "@tanstack/react-query"
import { createArticle } from "../api/blog.mutations"

/** Editor only: publishes an article, authored by the caller. */
export function useCreateArticle() {
  return useMutation({ mutationFn: createArticle })
}
