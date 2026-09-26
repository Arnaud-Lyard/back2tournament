import Link from "next/link"
import { getTranslations } from "next-intl/server"
import { Kbd } from "@/components/ui/kbd"
import { Separator } from "@/components/ui/separator"
import { hasPermission } from "@/features/auth/rbac/can"
import { getCurrentUser } from "@/features/auth/server/get-current-user"
import { siteNavItems } from "./nav-items"
import { SiteBrand } from "./site-brand"

interface FooterLink {
  href: string
  label: string
}

export async function SiteFooter() {
  const [t, tNav, tCommon, user] = await Promise.all([
    getTranslations("footer"),
    getTranslations("nav"),
    getTranslations("common"),
    getCurrentUser(),
  ])

  const platformLinks: FooterLink[] = siteNavItems
    .filter((item) => !item.permission && (!item.requiresAuth || user))
    .map((item) => ({ href: item.href, label: tNav(item.labelKey) }))

  const accountLinks: FooterLink[] = !user
    ? [
        { href: "/login", label: tNav("login") },
        { href: "/register", label: tNav("register") },
      ]
    : hasPermission(user.permissions, "backoffice:access")
      ? [{ href: "/backoffice", label: tNav("backoffice") }]
      : []

  return (
    <footer className="border-t bg-muted/30">
      <div className="mx-auto grid w-full max-w-6xl gap-8 px-4 py-10 sm:grid-cols-2 md:grid-cols-[2fr_1fr_1fr]">
        <div className="flex flex-col gap-3">
          <SiteBrand />
          <p className="max-w-sm text-sm text-muted-foreground">
            {t("tagline")}
          </p>
        </div>
        <FooterLinks title={t("platform")} links={platformLinks} />
        {accountLinks.length > 0 && (
          <FooterLinks title={t("account")} links={accountLinks} />
        )}
      </div>
      <Separator />
      <div className="mx-auto flex w-full max-w-6xl flex-col gap-2 px-4 py-6 text-xs text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
        <p>
          © {new Date().getFullYear()} {tCommon("appName")}. {t("rights")}
        </p>
        <p className="flex items-center gap-1.5">
          {t("themeHint")}
          <Kbd>D</Kbd>
        </p>
      </div>
    </footer>
  )
}

function FooterLinks({ title, links }: { title: string; links: FooterLink[] }) {
  return (
    <nav aria-label={title} className="flex flex-col gap-3">
      <h2 className="text-sm font-medium">{title}</h2>
      <ul className="flex flex-col gap-2 text-sm text-muted-foreground">
        {links.map((link) => (
          <li key={link.href}>
            <Link
              href={link.href}
              className="transition-colors hover:text-foreground"
            >
              {link.label}
            </Link>
          </li>
        ))}
      </ul>
    </nav>
  )
}
