import { HouseIcon, NewspaperIcon, type LucideIcon } from "lucide-react"
import type { AuthPermission } from "@/features/auth/types"

export interface NavItem {
  href: string
  labelKey: "home" | "blog"
  icon: LucideIcon
  match: "exact" | "prefix"
  requiresAuth: boolean
  permission?: AuthPermission
}

export const siteNavItems: readonly NavItem[] = [
  {
    href: "/",
    labelKey: "home",
    icon: HouseIcon,
    match: "exact",
    requiresAuth: false,
  },
  {
    href: "/blog",
    labelKey: "blog",
    icon: NewspaperIcon,
    match: "prefix",
    requiresAuth: false,
  },
]

export function isActivePath(
  pathname: string,
  href: string,
  match: NavItem["match"]
): boolean {
  if (match === "exact") return pathname === href
  return pathname === href || pathname.startsWith(`${href}/`)
}
