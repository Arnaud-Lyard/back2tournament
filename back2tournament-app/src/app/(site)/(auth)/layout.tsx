import type { ReactNode } from "react"
import { requireGuest } from "@/features/auth/rbac/require"

export default async function AuthLayout({
  children,
}: {
  children: ReactNode
}) {
  await requireGuest()

  return (
    <div className="flex flex-1 items-center justify-center px-4 py-12">
      {children}
    </div>
  )
}
