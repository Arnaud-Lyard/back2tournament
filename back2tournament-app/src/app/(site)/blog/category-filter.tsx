import Link from "next/link"
import { getTranslations } from "next-intl/server"
import { buttonVariants } from "@/components/ui/button"
import type { Category } from "@/features/blog/types"
import { listHref } from "@/libs/list-params"
import { cn } from "@/libs/utils"

interface CategoryFilterProps {
  categories: Category[]
  active: string
  search: string
}

export async function CategoryFilter({
  categories,
  active,
  search,
}: CategoryFilterProps) {
  const t = await getTranslations("blog")

  if (categories.length === 0) return null

  return (
    <nav aria-label={t("filterLabel")} className="flex flex-wrap gap-2">
      <Chip
        href={listHref("/blog", { q: search })}
        active={active === ""}
        label={t("allCategories")}
      />
      {categories.map((category) =>
        category.slug ? (
          <Chip
            key={category.id ?? category.slug}
            href={listHref("/blog", { q: search, category: category.slug })}
            active={active === category.slug}
            label={category.name ?? category.slug}
          />
        ) : null
      )}
    </nav>
  )
}

function Chip({
  href,
  active,
  label,
}: {
  href: string
  active: boolean
  label: string
}) {
  return (
    <Link
      href={href}
      aria-current={active ? "true" : undefined}
      className={cn(
        buttonVariants({ variant: active ? "secondary" : "ghost", size: "sm" }),
        "rounded-4xl"
      )}
    >
      {label}
    </Link>
  )
}
