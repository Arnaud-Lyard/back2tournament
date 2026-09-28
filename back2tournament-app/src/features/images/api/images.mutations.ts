import { fetchJson } from "@/libs/api/fetch-json"
import { toImageForm } from "../lib/image-file"

export interface UploadImageVariables {
  /** The route handler of this app the image goes to. */
  endpoint: string
  image: File
}

export interface RemoveImageVariables {
  /** The route handler of this app the image is taken from. */
  endpoint: string
}

/** Sends an image; answers the resource it now illustrates. */
export function uploadImage({
  endpoint,
  image,
}: UploadImageVariables): Promise<unknown> {
  return fetchJson<unknown>(endpoint, {
    method: "POST",
    body: toImageForm({ image }),
  })
}

/** Takes an image away; answers the resource without it. */
export function removeImage({
  endpoint,
}: RemoveImageVariables): Promise<unknown> {
  return fetchJson<unknown>(endpoint, { method: "DELETE" })
}
