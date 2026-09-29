"use client"

import { ArrowLeftIcon, ShieldIcon } from "lucide-react"
import Link from "next/link"
import { usePathname } from "next/navigation"
import { useTranslations } from "next-intl"
import { isActivePath } from "@/components/layout/nav-items"
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarRail,
  useSidebar,
} from "@/components/ui/sidebar"
import { useAuth } from "@/features/auth/hooks/use-auth"
import { BACKOFFICE_HOME, visibleBackofficeNav } from "./backoffice-nav"

export function BackofficeSidebar() {
  const t = useTranslations("backoffice.nav")
  const tRoles = useTranslations("roles")
  const pathname = usePathname()
  const { user } = useAuth()
  const { isMobile, setOpenMobile } = useSidebar()

  const groups = visibleBackofficeNav(user?.permissions ?? [])
  const closeOnMobile = () => {
    if (isMobile) setOpenMobile(false)
  }

  return (
    <Sidebar
      collapsible="icon"
      className="top-(--header-height) h-[calc(100svh-var(--header-height))]!"
    >
      <SidebarHeader>
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarMenuButton
              size="lg"
              render={<Link href={BACKOFFICE_HOME} onClick={closeOnMobile} />}
            >
              <span className="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                <ShieldIcon />
              </span>
              <span className="grid flex-1 text-left leading-tight">
                <span className="truncate font-medium">{t("title")}</span>
                {user && (
                  <span className="truncate text-xs text-sidebar-foreground/70">
                    {tRoles(user.role)}
                  </span>
                )}
              </span>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarHeader>
      <SidebarContent>
        {groups.map((group) => (
          <SidebarGroup key={group.labelKey}>
            <SidebarGroupLabel>{t(group.labelKey)}</SidebarGroupLabel>
            <SidebarGroupContent>
              <SidebarMenu>
                {group.items.map((item) => {
                  const active = isActivePath(
                    pathname,
                    item.href,
                    item.href === BACKOFFICE_HOME ? "exact" : "prefix"
                  )

                  return (
                    <SidebarMenuItem key={item.href}>
                      <SidebarMenuButton
                        isActive={active}
                        aria-current={active ? "page" : undefined}
                        tooltip={t(item.labelKey)}
                        render={
                          <Link href={item.href} onClick={closeOnMobile} />
                        }
                      >
                        <item.icon />
                        <span>{t(item.labelKey)}</span>
                      </SidebarMenuButton>
                    </SidebarMenuItem>
                  )
                })}
              </SidebarMenu>
            </SidebarGroupContent>
          </SidebarGroup>
        ))}
      </SidebarContent>
      <SidebarFooter>
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarMenuButton
              tooltip={t("backToSite")}
              render={<Link href="/" onClick={closeOnMobile} />}
            >
              <ArrowLeftIcon />
              <span>{t("backToSite")}</span>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarFooter>
      <SidebarRail />
    </Sidebar>
  )
}
