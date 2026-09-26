import type { Metadata } from "next"
import { getTranslations } from "next-intl/server"
import { PageHeader } from "@/components/layout/page-header"
import { requirePermission } from "@/features/auth/rbac/require"
import { loadCategories } from "@/features/blog/server/blog"
import { CategoriesManager } from "./categories-manager"

export async function generateMetadata(): Promise<Metadata> {
  const t = await getTranslations("backoffice.categories")
  return { title: t("title") }
}

export default async function BackofficeCategoriesPage() {
  const [, t, categories] = await Promise.all([
    requirePermission("category:manage"),
    getTranslations("backoffice.categories"),
    loadCategories(),
  ])

  return (
    <>
      <PageHeader title={t("title")} description={t("description")} />
      <CategoriesManager categories={categories.ok ? categories.data : null} />
    </>
  )
}
