import { fetchJson } from "@/libs/api/fetch-json"
import { toImageForm } from "../lib/image-file"

export interface UploadImageVariables {
  endpoint: string
  image: File
}

export interface RemoveImageVariables {
  endpoint: string
}

export function uploadImage({
  endpoint,
  image,
}: UploadImageVariables): Promise<unknown> {
  return fetchJson<unknown>(endpoint, {
    method: "POST",
    body: toImageForm({ image }),
  })
}

export function removeImage({
  endpoint,
}: RemoveImageVariables): Promise<unknown> {
  return fetchJson<unknown>(endpoint, { method: "DELETE" })
}
