/** The images the picker offers; the API reads the type from the content. */
export const IMAGE_TYPES = [
  "image/jpeg",
  "image/png",
  "image/webp",
  "image/gif",
] as const

/** The heaviest image the API takes: 8 MB. */
export const MAX_IMAGE_BYTES = 8 * 1024 * 1024

/** Why a file is refused before being sent. */
export type ImageFileProblem = "type" | "empty" | "size"

/**
 * Checks a file the way the API will, so that an obviously refused one is
 * not sent at all; null when it may be sent.
 */
export function imageFileProblem(
  file: Pick<File, "type" | "size">
): ImageFileProblem | null {
  if (!(IMAGE_TYPES as readonly string[]).includes(file.type)) return "type"
  if (file.size === 0) return "empty"
  if (file.size > MAX_IMAGE_BYTES) return "size"
  return null
}

/** An image as the multipart form the API reads: one `image` field. */
export function toImageForm({ image }: { image: Blob }): FormData {
  const form = new FormData()
  form.append("image", image)
  return form
}
