"use client"

import Link from "next/link"
import { useTranslations } from "next-intl"
import { LocaleSwitcher } from "@/components/locale-switcher"
import { ThemeToggle } from "@/components/theme-toggle"
import { buttonVariants } from "@/components/ui/button"
import { useAuth } from "@/features/auth/hooks/use-auth"
import { cn } from "@/libs/utils"
import { MainNav } from "./main-nav"
import { MobileNav } from "./mobile-nav"
import { SiteBrand } from "./site-brand"
import { UserMenu } from "./user-menu"

interface SiteHeaderProps {
  fluid?: boolean
}

export function SiteHeader({ fluid = false }: SiteHeaderProps) {
  const t = useTranslations("nav")
  const { isAuthenticated } = useAuth()

  return (
    <header className="sticky top-0 z-40 w-full border-b bg-background/80 backdrop-blur-md">
      <div
        className={cn(
          "mx-auto flex h-(--header-height) w-full items-center gap-2 px-4",
          !fluid && "max-w-6xl"
        )}
      >
        <MobileNav />
        <SiteBrand className="mr-2 md:mr-4" />
        <MainNav />
        <div className="ml-auto flex items-center gap-1.5">
          <LocaleSwitcher />
          <ThemeToggle />
          {isAuthenticated ? (
            <UserMenu />
          ) : (
            <>
              <Link
                href="/login"
                className={cn(buttonVariants({ variant: "ghost", size: "sm" }))}
              >
                {t("login")}
              </Link>
              <Link
                href="/register"
                className={cn(
                  buttonVariants({ size: "sm" }),
                  "hidden sm:inline-flex"
                )}
              >
                {t("register")}
              </Link>
            </>
          )}
        </div>
      </div>
    </header>
  )
}
