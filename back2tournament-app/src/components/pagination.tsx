import { ChevronLeftIcon, ChevronRightIcon } from "lucide-react"
import Link from "next/link"
import { getTranslations } from "next-intl/server"
import { buttonVariants } from "@/components/ui/button"
import { listHref, pageWindow } from "@/libs/list-params"
import { cn } from "@/libs/utils"

interface PaginationProps {
  page: number
  pages: number
  pathname: string
  params?: Record<string, string | number | undefined>
}

export async function Pagination({
  page,
  pages,
  pathname,
  params = {},
}: PaginationProps) {
  const t = await getTranslations("pagination")

  if (pages <= 1) return null

  const current = Math.min(Math.max(page, 1), pages)
  const hrefFor = (target: number) =>
    listHref(pathname, { ...params, page: target })

  return (
    <nav
      aria-label={t("label")}
      className="flex flex-wrap items-center justify-center gap-1"
    >
      <Step
        href={hrefFor(current - 1)}
        disabled={current <= 1}
        label={t("previous")}
      >
        <ChevronLeftIcon />
      </Step>
      <ul className="hidden items-center gap-1 sm:flex">
        {pageWindow(current, pages).map((target) => (
          <li key={target}>
            <Link
              href={hrefFor(target)}
              aria-current={target === current ? "page" : undefined}
              aria-label={t("goToPage", { page: target })}
              className={cn(
                buttonVariants({
                  variant: target === current ? "secondary" : "ghost",
                  size: "icon-sm",
                })
              )}
            >
              {target}
            </Link>
          </li>
        ))}
      </ul>
      <p className="px-2 text-sm text-muted-foreground sm:sr-only">
        {t("status", { page: current, pages })}
      </p>
      <Step
        href={hrefFor(current + 1)}
        disabled={current >= pages}
        label={t("next")}
      >
        <ChevronRightIcon />
      </Step>
    </nav>
  )
}

function Step({
  href,
  disabled,
  label,
  children,
}: {
  href: string
  disabled: boolean
  label: string
  children: React.ReactNode
}) {
  const className = cn(buttonVariants({ variant: "ghost", size: "icon-sm" }))

  if (disabled) {
    return (
      <span aria-hidden className={cn(className, "opacity-40")}>
        {children}
      </span>
    )
  }

  return (
    <Link href={href} aria-label={label} className={className}>
      {children}
    </Link>
  )
}
