import { z } from "zod"
import type messages from "@/messages/fr.json"

export type ValidationMessage = Extract<
  keyof (typeof messages)["validation"],
  string
>

/**
 * Schema messages are keys of the `validation` messages namespace: forms
 * translate them at display time, and the Route Handlers sharing the same
 * schemas never need to. Wrapping a key in `message()` checks it exists.
 */
export const message = (key: ValidationMessage) => key

const MAX_TEXT_LENGTH = 255

export function requiredText(maxLength = MAX_TEXT_LENGTH) {
  return z
    .string()
    .trim()
    .min(1, message("required"))
    .max(maxLength, message("tooLong"))
}

/** A name shown in lists: a clan or a team (50 characters), a tournament (100). */
export function requiredName(maxLength: 50 | 100 = 50) {
  return z
    .string()
    .trim()
    .min(1, message("required"))
    .max(maxLength, message(maxLength === 50 ? "tooLong50" : "tooLong100"))
}

/** Backend identifiers are v4 UUIDs; pasted values are trimmed and lowercased. */
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
