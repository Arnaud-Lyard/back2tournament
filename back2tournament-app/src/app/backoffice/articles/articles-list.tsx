import Link from "next/link"
import { getFormatter, getTranslations } from "next-intl/server"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { articleDate } from "@/features/blog/lib/publication"
import type { ArticleSummary, Category } from "@/features/blog/types"
import { listHref } from "@/libs/list-params"
import { ListCard } from "../list-card"
import { ArticleStatusBadge } from "./article-status-badge"

interface ArticlesListProps {
  articles: ArticleSummary[]
  categories: Category[]
  /** The page of the list, kept when an article is opened. */
  page: number
  /** The article shown in the preview, highlighted in the list. */
  selectedId?: string
}

export async function ArticlesList({
  articles,
  categories,
  page,
  selectedId,
}: ArticlesListProps) {
  const [t, format] = await Promise.all([
    getTranslations("backoffice.articles.list"),
    getFormatter(),
  ])
  const categoryNames = new Map(
    categories.map((category) => [category.id, category.name])
  )

  return (
    <ListCard
      title={t("title")}
      description={t("description")}
      count={articles.length}
      showCount={false}
      emptyTitle={t("emptyTitle")}
      emptyDescription={t("emptyDescription")}
    >
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>{t("columns.title")}</TableHead>
            <TableHead>{t("columns.status")}</TableHead>
            <TableHead>{t("columns.author")}</TableHead>
            <TableHead>{t("columns.category")}</TableHead>
            <TableHead>{t("columns.date")}</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {articles.map((article) => {
            const id = article.id?.value
            if (!id) return null
            const date = articleDate(article)

            return (
              <TableRow
                key={id}
                data-state={id === selectedId ? "selected" : undefined}
              >
                <TableCell className="max-w-44 truncate font-medium">
                  {/* Opens the article in the preview, above the list. */}
                  <Link
                    href={listHref("/backoffice/articles", {
                      page,
                      articleId: id,
                    })}
                    scroll={false}
                    className="underline-offset-4 hover:underline"
                  >
                    {article.title}
                  </Link>
                </TableCell>
                <TableCell>
                  <ArticleStatusBadge article={article} />
                </TableCell>
                <TableCell className="max-w-28 truncate">
                  {article.authorName ?? (
                    <span className="text-muted-foreground">
                      {t("noAuthor")}
                    </span>
                  )}
                </TableCell>
                <TableCell className="max-w-28 truncate">
                  {categoryNames.get(article.category?.value)}
                </TableCell>
                <TableCell>
                  {date
                    ? format.dateTime(new Date(date), { dateStyle: "short" })
                    : null}
                </TableCell>
              </TableRow>
            )
          })}
        </TableBody>
      </Table>
    </ListCard>
  )
}
