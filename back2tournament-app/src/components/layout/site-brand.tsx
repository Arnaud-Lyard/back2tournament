import { TrophyIcon } from "lucide-react"
import Link from "next/link"
import { useTranslations } from "next-intl"
import { cn } from "@/libs/utils"

export function SiteBrand({ className }: { className?: string }) {
  const t = useTranslations("common")

  return (
    <Link
      href="/"
      className={cn(
        "flex shrink-0 items-center gap-2 font-heading text-base font-semibold tracking-tight",
        className
      )}
    >
      <span className="flex size-7 items-center justify-center rounded-md bg-primary text-primary-foreground">
        <TrophyIcon className="size-4" />
      </span>
      {t("appName")}
    </Link>
  )
}
