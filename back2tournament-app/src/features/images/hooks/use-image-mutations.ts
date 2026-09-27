"use client"

import { useMutation } from "@tanstack/react-query"
import { removeImage, uploadImage } from "../api/images.mutations"

/** Sends an image to one of the image route handlers. */
export function useUploadImage() {
  return useMutation({ mutationFn: uploadImage })
}

/** Takes an image away through one of the image route handlers. */
export function useRemoveImage() {
  return useMutation({ mutationFn: removeImage })
}
