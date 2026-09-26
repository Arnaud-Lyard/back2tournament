"use client"

import { useRouter } from "next/navigation"
import { createContext, useCallback, useMemo, type ReactNode } from "react"
import type { LoginInput } from "../schemas/login.schema"
import type { RegisterInput } from "../schemas/register.schema"
import type { AuthPermission, AuthUser, PlayerProfile } from "../types"
import { fetchJson } from "@/libs/api/fetch-json"
import { hasPermission } from "../rbac/can"

/** signIn and signUp throw an ApiError, for useApiErrorMessage to word. */
interface AuthContextValue {
  user: AuthUser | null
  isAuthenticated: boolean
  signIn: (input: LoginInput) => Promise<void>
  signUp: (input: RegisterInput) => Promise<{ verified: boolean }>
  signOut: () => Promise<void>
  hasPermission: (permission: AuthPermission) => boolean
  /** The caller's player profile in this game, if they created one. */
  playerFor: (gameId: string) => PlayerProfile | undefined
}

export const AuthContext = createContext<AuthContextValue | undefined>(
  undefined
)

export interface AuthProviderProps {
  children: ReactNode
  initialUser: AuthUser | null
}

export function AuthProvider({ children, initialUser }: AuthProviderProps) {
  const router = useRouter()

  const signIn = useCallback(
    async (input: LoginInput) => {
      await fetchJson<AuthUser>("/api/auth/login", {
        method: "POST",
        body: input,
      })
      router.refresh()
    },
    [router]
  )

  const signUp = useCallback(async (input: RegisterInput) => {
    const body = await fetchJson<{ verified?: boolean }>("/api/auth/register", {
      method: "POST",
      body: input,
    })
    return { verified: body.verified ?? false }
  }, [])

  const signOut = useCallback(async () => {
    await fetch("/api/auth/logout", { method: "POST" })
    router.replace("/login")
    router.refresh()
  }, [router])

  const value = useMemo<AuthContextValue>(
    () => ({
      user: initialUser,
      isAuthenticated: !!initialUser,
      signIn,
      signUp,
      signOut,
      hasPermission: (permission) =>
        hasPermission(initialUser?.permissions, permission),
      playerFor: (gameId) => initialUser?.playersByGame[gameId],
    }),
    [initialUser, signIn, signUp, signOut]
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}
