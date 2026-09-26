import { ShieldIcon } from "lucide-react"
import type { Metadata } from "next"
import { getTranslations } from "next-intl/server"
import { PageContainer } from "@/components/layout/page-container"
import { PageHeader } from "@/components/layout/page-header"
import {
  Empty,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from "@/components/ui/empty"

export async function generateMetadata(): Promise<Metadata> {
  const t = await getTranslations("clans")
  return { title: t("title") }
}

export default async function ClansPage() {
  const t = await getTranslations("clans")

  return (
    <PageContainer>
      <PageHeader title={t("title")} description={t("description")} />
      <Empty className="border">
        <EmptyHeader>
          <EmptyMedia variant="icon">
            <ShieldIcon />
          </EmptyMedia>
          <EmptyTitle>{t("empty.title")}</EmptyTitle>
          <EmptyDescription>{t("empty.description")}</EmptyDescription>
        </EmptyHeader>
      </Empty>
    </PageContainer>
  )
}
