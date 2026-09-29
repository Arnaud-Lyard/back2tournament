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
