const MAX_SEARCH_LENGTH = 100

function first(value: string | string[] | undefined): string | undefined {
  return Array.isArray(value) ? value[0] : value
}

export function readPageParam(value: string | string[] | undefined): number {
  const raw = first(value)?.trim()
  if (!raw || !/^\d+$/.test(raw)) return 1

  const page = Number.parseInt(raw, 10)
  return page > 0 ? page : 1
}

export function readSearchParam(value: string | string[] | undefined): string {
  return (first(value) ?? "").trim().slice(0, MAX_SEARCH_LENGTH)
}

export function readSlugParam(value: string | string[] | undefined): string {
  const raw = first(value)?.trim().toLowerCase() ?? ""
  return /^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(raw) ? raw : ""
}

export function listHref(
  pathname: string,
  params: Record<string, string | number | undefined>
): string {
  const query = new URLSearchParams()

  for (const [key, value] of Object.entries(params)) {
    if (value === undefined || value === "" || value === 0) continue
    if (key === "page" && value === 1) continue
    query.set(key, String(value))
  }

  const search = query.toString()
  return search ? `${pathname}?${search}` : pathname
}

export function pageWindow(page: number, pages: number, size = 5): number[] {
  if (pages <= 0) return []

  const width = Math.min(size, pages)
  const start = Math.min(
    Math.max(1, page - Math.floor(width / 2)),
    pages - width + 1
  )

  return Array.from({ length: width }, (_, index) => start + index)
}
