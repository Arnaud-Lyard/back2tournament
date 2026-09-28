import { decodeJwt } from "jose"
import { FALLBACK_COOKIE_MAX_AGE } from "./cookie"

interface SymfonyJwtPayload {
  username?: string
  roles?: string[]
  exp?: number
  iat?: number
}

export function decodeSymfonyJwt(token: string): SymfonyJwtPayload | null {
  try {
    return decodeJwt(token) as SymfonyJwtPayload
  } catch {
    return null
  }
}

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
