import type { ReactNode } from "react"
import { requireUser } from "@/features/auth/rbac/require"

export default async function DashboardLayout({
  children,
}: {
  children: ReactNode
}) {
  await requireUser()

  return <div className="flex flex-1 flex-col">{children}</div>
}
