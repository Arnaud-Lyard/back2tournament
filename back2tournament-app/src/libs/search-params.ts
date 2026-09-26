import { uuid } from "@/libs/validation"

export type UuidParam =
  | { kind: "missing" }
  | { kind: "invalid"; raw: string }
  | { kind: "valid"; id: string }

export function readUuidParam(value: string | string[] | undefined): UuidParam {
  const raw = Array.isArray(value) ? value[0] : value
  if (!raw?.trim()) return { kind: "missing" }

  const parsed = uuid().safeParse(raw)
  return parsed.success
    ? { kind: "valid", id: parsed.data }
    : { kind: "invalid", raw }
}

export function readUuidSegment(value: string | undefined): string | null {
  const parsed = uuid().safeParse(value ?? "")
  return parsed.success ? parsed.data : null
}
