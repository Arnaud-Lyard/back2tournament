const DEFAULT_LENGTH = 180

export function toExcerpt(
  body: string | null | undefined,
  maxLength = DEFAULT_LENGTH
): string {
  const text = (body ?? "").replace(/\s+/g, " ").trim()
  if (text.length <= maxLength) return text

  const cut = text.slice(0, maxLength)
  const lastSpace = cut.lastIndexOf(" ")

  return `${(lastSpace > 0 ? cut.slice(0, lastSpace) : cut).trimEnd()}…`
}
