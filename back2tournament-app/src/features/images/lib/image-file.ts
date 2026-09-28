export const IMAGE_TYPES = ["image/jpeg", "image/png", "image/webp"] as const

export const MAX_IMAGE_BYTES = 8 * 1024 * 1024

export type ImageFileProblem = "type" | "empty" | "size"

export function imageFileProblem(
  file: Pick<File, "type" | "size">
): ImageFileProblem | null {
  if (!(IMAGE_TYPES as readonly string[]).includes(file.type)) return "type"
  if (file.size === 0) return "empty"
  if (file.size > MAX_IMAGE_BYTES) return "size"
  return null
}

export function toImageForm({ image }: { image: Blob }): FormData {
  const form = new FormData()
  form.append("image", image)
  return form
}
