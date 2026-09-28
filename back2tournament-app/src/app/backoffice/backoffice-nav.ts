import {
  Gamepad2Icon,
  GavelIcon,
  LayoutDashboardIcon,
  NewspaperIcon,
  TagsIcon,
  type LucideIcon,
} from "lucide-react"
import { hasPermission } from "@/features/auth/rbac/can"
import type { AuthPermission } from "@/features/auth/types"

export interface BackofficeNavItem {
  href: string
  labelKey: "dashboard" | "games" | "fights" | "articles" | "categories"
  icon: LucideIcon
  permission: AuthPermission
}

interface BackofficeNavGroup {
  labelKey: "general" | "competition" | "content"
  items: readonly BackofficeNavItem[]
}

export const BACKOFFICE_HOME = "/backoffice"

export const backofficeNav: readonly BackofficeNavGroup[] = [
  {
    labelKey: "general",
    items: [
      {
        href: BACKOFFICE_HOME,
        labelKey: "dashboard",
        icon: LayoutDashboardIcon,
        permission: "backoffice:access",
      },
    ],
  },
  {
    labelKey: "competition",
    items: [
      {
        href: "/backoffice/games",
        labelKey: "games",
        icon: Gamepad2Icon,
        permission: "game:manage",
      },
      {
        href: "/backoffice/fights",
        labelKey: "fights",
        icon: GavelIcon,
        permission: "fight:manage",
      },
    ],
  },
  {
    labelKey: "content",
    items: [
      {
        href: "/backoffice/articles",
        labelKey: "articles",
        icon: NewspaperIcon,
        permission: "article:create",
      },
      {
        href: "/backoffice/categories",
        labelKey: "categories",
        icon: TagsIcon,
        permission: "category:manage",
      },
    ],
  },
]

export function visibleBackofficeNav(permissions: readonly AuthPermission[]) {
  return backofficeNav
    .map((group) => ({
      ...group,
      items: group.items.filter((item) =>
        hasPermission(permissions, item.permission)
      ),
    }))
    .filter((group) => group.items.length > 0)
}
