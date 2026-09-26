import { EyeIcon } from "lucide-react"
import Link from "next/link"
import { getFormatter, getTranslations } from "next-intl/server"
import { buttonVariants } from "@/components/ui/button"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import type { ArticleSummary, Category } from "@/features/blog/types"
import { cn } from "@/libs/utils"
import { ListCard } from "../list-card"

interface ArticlesListProps {
  articles: ArticleSummary[]
  categories: Category[]
  /** The article shown in the preview, highlighted in the list. */
  selectedId?: string
}

export async function ArticlesList({
  articles,
  categories,
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
      emptyTitle={t("emptyTitle")}
      emptyDescription={t("emptyDescription")}
    >
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>{t("columns.title")}</TableHead>
            <TableHead>{t("columns.category")}</TableHead>
            <TableHead>{t("columns.publishedAt")}</TableHead>
            <TableHead className="sr-only">{t("columns.actions")}</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {articles.map((article) => {
            const id = article.id?.value
            if (!id) return null

            return (
              <TableRow
                key={id}
                data-state={id === selectedId ? "selected" : undefined}
              >
                <TableCell className="max-w-64 truncate font-medium">
                  {article.title}
                </TableCell>
                <TableCell>
                  {categoryNames.get(article.category?.value)}
                </TableCell>
                <TableCell>
                  {article.createdAt
                    ? format.dateTime(new Date(article.createdAt), {
                        dateStyle: "medium",
                      })
                    : null}
                </TableCell>
                <TableCell className="text-right">
                  <Link
                    href={`/backoffice/articles?articleId=${encodeURIComponent(id)}`}
                    scroll={false}
                    className={cn(
                      buttonVariants({ variant: "ghost", size: "sm" })
                    )}
                  >
                    <EyeIcon data-icon="inline-start" />
                    {t("view")}
                  </Link>
                </TableCell>
              </TableRow>
            )
          })}
        </TableBody>
      </Table>
    </ListCard>
  )
}
