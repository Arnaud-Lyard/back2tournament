import type { Metadata } from "next"
import { getTranslations } from "next-intl/server"
import { ImagePicker } from "@/components/image-picker"
import { PageContainer } from "@/components/layout/page-container"
import { PageHeader } from "@/components/layout/page-header"
import { Badge } from "@/components/ui/badge"
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { requireUser } from "@/features/auth/rbac/require"

export async function generateMetadata(): Promise<Metadata> {
  const t = await getTranslations("account")
  return { title: t("title") }
}

export default async function AccountPage() {
  const [user, t, tRoles] = await Promise.all([
    requireUser(),
    getTranslations("account"),
    getTranslations("roles"),
  ])

  return (
    <PageContainer>
      <PageHeader title={t("title")} description={t("description")} />
      <div className="grid max-w-3xl gap-6">
        <Card>
          <CardHeader>
            <CardTitle>{t("avatar.title")}</CardTitle>
            <CardDescription>{t("avatar.description")}</CardDescription>
          </CardHeader>
          <CardContent>
            <ImagePicker
              endpoint="/api/users/me/avatar"
              image={user.avatar}
              name={user.username}
              shape="round"
            />
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle>{t("identity.title")}</CardTitle>
          </CardHeader>
          <CardContent>
            <dl className="grid grid-cols-[auto_minmax(0,1fr)] items-center gap-x-6 gap-y-2">
              <dt className="text-muted-foreground">
                {t("identity.username")}
              </dt>
              <dd className="truncate font-medium">{user.username}</dd>
              <dt className="text-muted-foreground">{t("identity.role")}</dt>
              <dd>
                <Badge variant="secondary">{tRoles(user.role)}</Badge>
              </dd>
            </dl>
          </CardContent>
        </Card>
      </div>
    </PageContainer>
  )
}
