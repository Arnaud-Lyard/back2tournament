"use client"

import { useMutation } from "@tanstack/react-query"
import { changeArticleStatus } from "../api/blog.mutations"

export function useChangeArticleStatus() {
  return useMutation({ mutationFn: changeArticleStatus })
}
