"use client"

import { usePathname } from "next/navigation"
import { useAuth } from "@/features/auth/hooks/use-auth"
import { isActivePath, siteNavItems } from "./nav-items"

/** The links the current visitor may follow, flagged when they are the current page. */
export function useSiteNav() {
  const pathname = usePathname()
  const { isAuthenticated, hasPermission } = useAuth()

  return siteNavItems
    .filter(
      (item) =>
        (!item.requiresAuth || isAuthenticated) &&
        (!item.permission || hasPermission(item.permission))
    )
    .map((item) => ({
      ...item,
      active: isActivePath(pathname, item.href, item.match),
    }))
}
