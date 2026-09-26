import { ArrowRightIcon } from "lucide-react"
import type { Metadata } from "next"
import Link from "next/link"
import { getTranslations } from "next-intl/server"
import { PageHeader } from "@/components/layout/page-header"
import { Badge } from "@/components/ui/badge"
import { buttonVariants } from "@/components/ui/button"
import { cn } from "@/libs/utils"
import {
  Card,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { requirePermission } from "@/features/auth/rbac/require"
import {
  BACKOFFICE_HOME,
  visibleBackofficeNav,
  type BackofficeNavItem,
} from "./backoffice-nav"

type Section = BackofficeNavItem & {
  labelKey: Exclude<BackofficeNavItem["labelKey"], "dashboard">
}

export async function generateMetadata(): Promise<Metadata> {
  const t = await getTranslations("backoffice.dashboard")
  return { title: t("title") }
}

export default async function BackofficeDashboardPage() {
  const [user, t, tNav, tRoles] = await Promise.all([
    requirePermission("backoffice:access"),
    getTranslations("backoffice.dashboard"),
    getTranslations("backoffice.nav"),
    getTranslations("roles"),
  ])

  const sections = visibleBackofficeNav(user.permissions)
    .flatMap((group) => group.items)
    .filter((item): item is Section => item.href !== BACKOFFICE_HOME)

  return (
    <>
      <PageHeader
        title={t("title")}
        description={t("description", { username: user.username })}
        actions={<Badge variant="secondary">{tRoles(user.role)}</Badge>}
      />
      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        {sections.map((section) => (
          <Card key={section.href}>
            <CardHeader>
              <span className="mb-2 flex size-10 items-center justify-center rounded-lg bg-muted [&_svg]:size-5">
                <section.icon />
              </span>
              <CardTitle>{tNav(section.labelKey)}</CardTitle>
              <CardDescription>
                {t(`sections.${section.labelKey}`)}
              </CardDescription>
            </CardHeader>
            <CardFooter className="mt-auto">
              <Link
                href={section.href}
                className={cn(
                  buttonVariants({ variant: "outline", size: "sm" })
                )}
              >
                {t("open")}
                <ArrowRightIcon data-icon="inline-end" />
              </Link>
            </CardFooter>
          </Card>
        ))}
      </div>
    </>
  )
}
