import { getTranslations } from "next-intl/server"
import { Badge } from "@/components/ui/badge"
import { isPublished } from "@/features/blog/lib/publication"
import type { ArticleSummary } from "@/features/blog/types"

export async function ArticleStatusBadge({
  article,
}: {
  article: Pick<ArticleSummary, "status">
}) {
  const t = await getTranslations("backoffice.articles.status")

  return isPublished(article) ? (
    <Badge>{t("published")}</Badge>
  ) : (
    <Badge variant="outline">{t("draft")}</Badge>
  )
}
