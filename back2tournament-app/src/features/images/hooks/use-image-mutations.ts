"use client"

import { useMutation } from "@tanstack/react-query"
import { removeImage, uploadImage } from "../api/images.mutations"

export function useUploadImage() {
  return useMutation({ mutationFn: uploadImage })
}

export function useRemoveImage() {
  return useMutation({ mutationFn: removeImage })
}
