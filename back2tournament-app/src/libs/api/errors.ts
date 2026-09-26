/**
 * The few backend failures the UI words differently than their status alone
 * would: both register conflicts answer 409, and an unverified account gets
 * the same 401 as a wrong password. The backend's messages stay in English
 * and are never shown; they are only matched here, to pick a translation.
 */
const KNOWN_BACKEND_ERRORS = {
  "email already used": "emailTaken",
  "username already used": "usernameTaken",
  "please verify your email before logging in": "emailNotVerified",
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

/**
 * Maps a Symfony error body into a typed ApiError: business failures answer
 * `{error: string}`, while the JWT authenticator's 401 answers
 * `{code, message}`.
 */
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

/** Reads back a code a Route Handler relayed, ignoring anything unknown. */
export function parseApiErrorCode(value: unknown): ApiErrorCode | undefined {
  const codes: readonly unknown[] = Object.values(KNOWN_BACKEND_ERRORS)
  return codes.includes(value) ? (value as ApiErrorCode) : undefined
}

function readString(body: unknown, key: string): string | undefined {
  if (!body || typeof body !== "object" || !(key in body)) return undefined
  const value = (body as Record<string, unknown>)[key]
  return typeof value === "string" ? value : undefined
}
