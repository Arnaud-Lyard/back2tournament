"use client"

import { LogInIcon, LogOutIcon, MenuIcon, UserPlusIcon } from "lucide-react"
import Link from "next/link"
import { useTranslations } from "next-intl"
import { useState } from "react"
import { Button, buttonVariants } from "@/components/ui/button"
import { cn } from "@/libs/utils"
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetFooter,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from "@/components/ui/sheet"
import { useAuth } from "@/features/auth/hooks/use-auth"
import { useSiteNav } from "./use-site-nav"

export function MobileNav() {
  const t = useTranslations("nav")
  const tCommon = useTranslations("common")
  const items = useSiteNav()
  const { isAuthenticated, signOut } = useAuth()
  const [open, setOpen] = useState(false)

  const close = () => setOpen(false)

  return (
    <Sheet open={open} onOpenChange={setOpen}>
      <SheetTrigger
        render={<Button variant="ghost" size="icon" className="md:hidden" />}
      >
        <MenuIcon />
        <span className="sr-only">{t("openMenu")}</span>
      </SheetTrigger>
      <SheetContent side="left">
        <SheetHeader>
          <SheetTitle>{tCommon("appName")}</SheetTitle>
          <SheetDescription>{t("menuDescription")}</SheetDescription>
        </SheetHeader>
        <nav aria-label={t("mainLabel")} className="flex flex-col gap-1 px-4">
          {items.map((item) => (
            <Link
              key={item.href}
              href={item.href}
              onClick={close}
              aria-current={item.active ? "page" : undefined}
              className={cn(
                buttonVariants({
                  variant: item.active ? "secondary" : "ghost",
                }),
                "justify-start"
              )}
            >
              <item.icon data-icon="inline-start" />
              {t(item.labelKey)}
            </Link>
          ))}
        </nav>
        <SheetFooter>
          {isAuthenticated ? (
            <Button
              variant="outline"
              onClick={() => {
                close()
                void signOut()
              }}
            >
              <LogOutIcon data-icon="inline-start" />
              {t("logout")}
            </Button>
          ) : (
            <>
              <Link
                href="/register"
                onClick={close}
                className={cn(buttonVariants())}
              >
                <UserPlusIcon data-icon="inline-start" />
                {t("register")}
              </Link>
              <Link
                href="/login"
                onClick={close}
                className={cn(buttonVariants({ variant: "outline" }))}
              >
                <LogInIcon data-icon="inline-start" />
                {t("login")}
              </Link>
            </>
          )}
        </SheetFooter>
      </SheetContent>
    </Sheet>
  )
}
