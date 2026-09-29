import { z } from "zod"
import type messages from "@/messages/fr.json"

export type ValidationMessage = Extract<
  keyof (typeof messages)["validation"],
  string
>

export const message = (key: ValidationMessage) => key

const MAX_TEXT_LENGTH = 255

export function requiredText(maxLength = MAX_TEXT_LENGTH) {
  return z
    .string()
    .trim()
    .min(1, message("required"))
    .max(maxLength, message("tooLong"))
}

export function requiredName(maxLength: 50 | 100 = 50) {
  return z
    .string()
    .trim()
    .min(1, message("required"))
    .max(maxLength, message(maxLength === 50 ? "tooLong50" : "tooLong100"))
}

export function uuid() {
  return z
    .string()
    .trim()
    .toLowerCase()
    .min(1, message("required"))
    .pipe(z.uuid(message("invalidUuid")))
}

export function slug() {
  return requiredText().regex(
    /^[a-z0-9]+(?:-[a-z0-9]+)*$/,
    message("invalidSlug")
  )
}
