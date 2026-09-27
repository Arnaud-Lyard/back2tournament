"use client"

import { useMutation } from "@tanstack/react-query"
import { updateArticle } from "../api/blog.mutations"

/** Editor only: changes the title, the body or the category of an article. */
export function useUpdateArticle() {
  return useMutation({ mutationFn: updateArticle })
}
