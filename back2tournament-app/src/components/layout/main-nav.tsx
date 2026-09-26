"use client"

import Link from "next/link"
import { useTranslations } from "next-intl"
import {
  NavigationMenu,
  NavigationMenuItem,
  NavigationMenuLink,
  NavigationMenuList,
  navigationMenuTriggerStyle,
} from "@/components/ui/navigation-menu"
import { useSiteNav } from "./use-site-nav"

/** Desktop navigation; below `md` the MobileNav sheet takes over. */
export function MainNav() {
  const t = useTranslations("nav")
  const items = useSiteNav()

  return (
    <NavigationMenu aria-label={t("mainLabel")} className="hidden md:flex">
      <NavigationMenuList>
        {items.map((item) => (
          <NavigationMenuItem key={item.href}>
            <NavigationMenuLink
              active={item.active}
              className={navigationMenuTriggerStyle()}
              render={<Link href={item.href} />}
            >
              {t(item.labelKey)}
            </NavigationMenuLink>
          </NavigationMenuItem>
        ))}
      </NavigationMenuList>
    </NavigationMenu>
  )
}
