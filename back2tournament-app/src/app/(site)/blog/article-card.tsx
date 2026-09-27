import Link from "next/link"
import { getFormatter, getTranslations } from "next-intl/server"
import { MediaPlaceholder } from "@/components/media-placeholder"
import { Badge } from "@/components/ui/badge"
import { Card, CardDescription, CardTitle } from "@/components/ui/card"
import { toExcerpt } from "@/features/blog/lib/excerpt"
import { articleDate } from "@/features/blog/lib/publication"
import type { ArticleSummary, Category } from "@/features/blog/types"

interface ArticleCardProps {
  article: ArticleSummary
  categories: Category[]
}

export async function ArticleCard({ article, categories }: ArticleCardProps) {
  const [t, format] = await Promise.all([
    getTranslations("blog"),
    getFormatter(),
  ])

  const id = article.id?.value
  if (!id) return null

  const category = categories.find(
    (candidate) => candidate.id === article.category?.value
  )
  const date = articleDate(article)

  return (
    <li>
      <Card className="group h-full gap-0 overflow-hidden p-0 transition-colors hover:border-primary/50">
        <Link
          href={`/blog/${encodeURIComponent(id)}`}
          className="flex h-full flex-col rounded-[inherit] outline-none focus-visible:ring-3 focus-visible:ring-ring/50"
        >
          <MediaPlaceholder />
          <div className="flex flex-1 flex-col gap-2 p-4">
            <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
              {category && <Badge variant="secondary">{category.name}</Badge>}
              {date && (
                <time dateTime={date}>
                  {format.dateTime(new Date(date), { dateStyle: "medium" })}
                </time>
              )}
              {article.authorName && (
                <span>{t("byAuthor", { author: article.authorName })}</span>
              )}
            </div>
            <CardTitle className="text-lg">{article.title}</CardTitle>
            <CardDescription className="line-clamp-3">
              {toExcerpt(article.body)}
            </CardDescription>
            <span className="mt-auto pt-2 text-sm font-medium text-primary">
              {t("readMore")}
            </span>
          </div>
        </Link>
      </Card>
    </li>
  )
}
