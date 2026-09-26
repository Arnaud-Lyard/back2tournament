import { decodeJwt } from "jose"
import { FALLBACK_COOKIE_MAX_AGE } from "./cookie"

interface SymfonyJwtPayload {
  username?: string
  roles?: string[]
  exp?: number
  iat?: number
}

/**
 * Decodes the JWT's payload WITHOUT verifying its signature — this app does
 * not hold the Symfony backend's signing key. This is only ever used for
 * fast-path UI/redirect decisions; Symfony remains the sole authorization
 * enforcer on every real API call.
 */
export function decodeSymfonyJwt(token: string): SymfonyJwtPayload | null {
  try {
    return decodeJwt(token) as SymfonyJwtPayload
  } catch {
    return null
  }
}

/** Seconds remaining until the token's `exp` claim, for the cookie's maxAge. */
export function getJwtExpirySeconds(token: string): number {
  const payload = decodeSymfonyJwt(token)
  if (!payload?.exp) return FALLBACK_COOKIE_MAX_AGE

  const remaining = payload.exp - Math.floor(Date.now() / 1000)
  return remaining > 0 ? remaining : FALLBACK_COOKIE_MAX_AGE
}

export function isJwtExpired(token: string): boolean {
  const payload = decodeSymfonyJwt(token)
  if (!payload?.exp) return false
  return payload.exp <= Math.floor(Date.now() / 1000)
}
