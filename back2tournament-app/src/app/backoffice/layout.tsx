import type { Metadata } from "next"
import { cookies } from "next/headers"
import type { ReactNode } from "react"
import { SiteHeader } from "@/components/layout/site-header"
import { SidebarInset, SidebarProvider } from "@/components/ui/sidebar"
import { requirePermission } from "@/features/auth/rbac/require"
import { BackofficeSidebar } from "./backoffice-sidebar"
import { BackofficeTopbar } from "./backoffice-topbar"

export const metadata: Metadata = {
  robots: { index: false, follow: false },
}

export default async function BackofficeLayout({
  children,
}: {
  children: ReactNode
}) {
  await requirePermission("backoffice:access")

  const sidebarState = (await cookies()).get("sidebar_state")?.value

  return (
    <SidebarProvider
      defaultOpen={sidebarState !== "false"}
      className="flex-col"
    >
      <SiteHeader fluid />
      <div className="flex flex-1">
        <BackofficeSidebar />
        <SidebarInset>
          <BackofficeTopbar />
          <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
            {children}
          </div>
        </SidebarInset>
      </div>
    </SidebarProvider>
  )
}
