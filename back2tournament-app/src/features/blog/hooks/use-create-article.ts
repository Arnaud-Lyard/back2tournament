"use client"

import { useMutation } from "@tanstack/react-query"
import { createArticle } from "../api/blog.mutations"

/** Editor only: writes an article, as a draft until someone publishes it. */
export function useCreateArticle() {
  return useMutation({ mutationFn: createArticle })
}
