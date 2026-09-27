/**
 * Reads formats typed as "1, 2, 5" (or "1v1 2v2 5v5"): players per side,
 * each once, in order. A list that holds anything else reads as none.
 */
export function parseTeamSizes(text: string): number[] {
  const tokens = text
    .split(/[\s,;]+/)
    .map((token) => token.trim().toLowerCase())
    .filter(Boolean)

  const sizes: number[] = []
  for (const token of tokens) {
    const match = /^(\d+)(?:v(\d+))?$/.exec(token)
    if (!match || (match[2] !== undefined && match[2] !== match[1])) return []
    sizes.push(Number(match[1]))
  }

  return [...new Set(sizes)].sort((one, two) => one - two)
}
