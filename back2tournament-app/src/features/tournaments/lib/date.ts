/**
 * A `datetime-local` value is the visitor's wall-clock time with no offset:
 * it is sent with theirs, so the backend reads the moment they meant.
 */
export function toIsoWithOffset(local: string): string {
  if (!local) return ""
  const date = new Date(local)
  if (Number.isNaN(date.getTime())) return ""

  const offset = -date.getTimezoneOffset()
  const sign = offset >= 0 ? "+" : "-"
  const hours = String(Math.floor(Math.abs(offset) / 60)).padStart(2, "0")
  const minutes = String(Math.abs(offset) % 60).padStart(2, "0")
  const pad = (value: number) => String(value).padStart(2, "0")

  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}:00${sign}${hours}:${minutes}`
}
