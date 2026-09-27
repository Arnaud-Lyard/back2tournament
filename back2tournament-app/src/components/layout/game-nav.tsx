"use client"

import {
  ShieldIcon,
  TrophyIcon,
  UsersIcon,
  type LucideIcon,
} from "lucide-react"
import Link from "next/link"
import { usePathname } from "next/navigation"
import { useTranslations } from "next-intl"
import { cn } from "@/libs/utils"

type SectionKey = "players" | "clans" | "rankings"

const SECTIONS: readonly { key: SectionKey; icon: LucideIcon }[] = [
  { key: "players", icon: UsersIcon },
  { key: "clans", icon: ShieldIcon },
  { key: "rankings", icon: TrophyIcon },
]

export function GameNav({ gameId, title }: { gameId: string; title: string }) {
  const t = useTranslations("games.nav")
  const pathname = usePathname()
  const base = `/games/${encodeURIComponent(gameId)}`

  return (
    <div className="sticky top-(--header-height) z-30 w-full border-b bg-background/80 backdrop-blur-md">
      <div className="mx-auto flex w-full max-w-6xl items-center gap-4 overflow-x-auto px-4">
        <span className="hidden shrink-0 py-2.5 text-sm font-medium text-muted-foreground md:inline">
          {title}
        </span>
        <nav
          aria-label={t("label", { title })}
          className="flex items-center gap-1"
        >
          {SECTIONS.map((section) => {
            const href = `${base}/${section.key}`
            const active = pathname === href || pathname.startsWith(`${href}/`)

            return (
              <Link
                key={section.key}
                href={href}
                aria-current={active ? "page" : undefined}
                className={cn(
                  "inline-flex items-center gap-1.5 border-b-2 px-3 py-2.5 text-sm font-medium whitespace-nowrap transition-colors",
                  active
                    ? "border-primary text-foreground"
                    : "border-transparent text-muted-foreground hover:text-foreground"
                )}
              >
                <section.icon className="size-4" />
                {t(section.key)}
              </Link>
            )
          })}
        </nav>
      </div>
    </div>
  )
}
