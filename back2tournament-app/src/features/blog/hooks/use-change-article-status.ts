"use client"

import { useMutation } from "@tanstack/react-query"
import { changeArticleStatus } from "../api/blog.mutations"

/**
 * Editor only: publishes an article, the caller becoming its author, or
 * takes it back to draft.
 */
export function useChangeArticleStatus() {
  return useMutation({ mutationFn: changeArticleStatus })
}
