"use client"

import Link from "next/link"
import { usePathname } from "next/navigation"
import { useTranslations } from "next-intl"
import { isActivePath } from "@/components/layout/nav-items"
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from "@/components/ui/breadcrumb"
import { Separator } from "@/components/ui/separator"
import { SidebarTrigger } from "@/components/ui/sidebar"
import { BACKOFFICE_HOME, backofficeNav } from "./backoffice-nav"

export function BackofficeTopbar() {
  const t = useTranslations("backoffice.nav")
  const pathname = usePathname()

  const section = backofficeNav
    .flatMap((group) => group.items)
    .find(
      (item) =>
        item.href !== BACKOFFICE_HOME &&
        isActivePath(pathname, item.href, "prefix")
    )

  return (
    <div className="flex h-12 shrink-0 items-center gap-2 border-b px-4">
      <SidebarTrigger className="-ml-1" aria-label={t("toggleSidebar")} />
      <Separator orientation="vertical" className="mr-2 data-vertical:h-4" />
      <Breadcrumb>
        <BreadcrumbList>
          <BreadcrumbItem>
            {section ? (
              <BreadcrumbLink render={<Link href={BACKOFFICE_HOME} />}>
                {t("title")}
              </BreadcrumbLink>
            ) : (
              <BreadcrumbPage>{t("title")}</BreadcrumbPage>
            )}
          </BreadcrumbItem>
          {section && (
            <>
              <BreadcrumbSeparator />
              <BreadcrumbItem>
                <BreadcrumbPage>{t(section.labelKey)}</BreadcrumbPage>
              </BreadcrumbItem>
            </>
          )}
        </BreadcrumbList>
      </Breadcrumb>
    </div>
  )
}
