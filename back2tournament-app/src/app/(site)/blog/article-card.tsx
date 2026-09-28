import Link from "next/link"
import { getFormatter, getLocale, getTranslations } from "next-intl/server"
import { StoredImage } from "@/components/stored-image"
import { Badge } from "@/components/ui/badge"
import { Card, CardDescription, CardTitle } from "@/components/ui/card"
import { toExcerpt } from "@/features/blog/lib/excerpt"
import { localizeArticle } from "@/features/blog/lib/localize"
import { articleDate } from "@/features/blog/lib/publication"
import type { ArticleSummary, Category } from "@/features/blog/types"

interface ArticleCardProps {
  article: ArticleSummary
  categories: Category[]
}

export async function ArticleCard({ article, categories }: ArticleCardProps) {
  const [t, format, locale] = await Promise.all([
    getTranslations("blog"),
    getFormatter(),
    getLocale(),
  ])

  const id = article.id?.value
  if (!id) return null

  const category = categories.find(
    (candidate) => candidate.id === article.category?.value
  )
  const date = articleDate(article)
  const { title, body, lang, untranslated } = localizeArticle(article, locale)

  return (
    <li>
      <Card className="group h-full gap-0 overflow-hidden p-0 transition-colors hover:border-primary/50">
        <Link
          href={`/blog/${encodeURIComponent(id)}`}
          className="flex h-full flex-col rounded-[inherit] outline-none focus-visible:ring-3 focus-visible:ring-ring/50"
        >
          <StoredImage src={article.image} />
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
              {untranslated && (
                <Badge variant="outline">{t("frenchOnly")}</Badge>
              )}
            </div>
            <CardTitle className="text-lg" lang={lang}>
              {title}
            </CardTitle>
            <CardDescription className="line-clamp-3" lang={lang}>
              {toExcerpt(body)}
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
