const KNOWN_BACKEND_ERRORS = {
  "email already used": "emailTaken",
  "username already used": "usernameTaken",
  "please verify your email before logging in": "emailNotVerified",
  "this player profile takes part in fights and cannot be deleted":
    "playerHasFights",
  "this player profile leads a clan and cannot be deleted": "playerLeadsClan",
  "this player profile belongs to a clan: leave it first": "playerInClan",
  "this player profile is invited to a clan: decline the invitation first":
    "playerInvitedToClan",
  "this player profile asks to join a clan: withdraw the request first":
    "playerAsksToJoinClan",
} as const

export type ApiErrorCode =
  (typeof KNOWN_BACKEND_ERRORS)[keyof typeof KNOWN_BACKEND_ERRORS]

export class ApiError extends Error {
  constructor(
    message: string,
    public readonly status: number,
    public readonly code?: ApiErrorCode
  ) {
    super(message)
    this.name = "ApiError"
  }
}

export function toApiError(status: number, body: unknown): ApiError {
  const message =
    readString(body, "error") ??
    readString(body, "message") ??
    `Request failed with status ${status}`

  return new ApiError(message, status, codeForBackendMessage(message))
}

function codeForBackendMessage(message: string): ApiErrorCode | undefined {
  return Object.hasOwn(KNOWN_BACKEND_ERRORS, message)
    ? KNOWN_BACKEND_ERRORS[message as keyof typeof KNOWN_BACKEND_ERRORS]
    : undefined
}

export function parseApiErrorCode(value: unknown): ApiErrorCode | undefined {
  const codes: readonly unknown[] = Object.values(KNOWN_BACKEND_ERRORS)
  return codes.includes(value) ? (value as ApiErrorCode) : undefined
}

function readString(body: unknown, key: string): string | undefined {
  if (!body || typeof body !== "object" || !(key in body)) return undefined
  const value = (body as Record<string, unknown>)[key]
  return typeof value === "string" ? value : undefined
}
