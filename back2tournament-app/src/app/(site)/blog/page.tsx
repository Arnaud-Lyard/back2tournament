import { NewspaperIcon } from "lucide-react"
import type { Metadata } from "next"
import { getTranslations } from "next-intl/server"
import { ListSearch } from "@/components/list-search"
import { PageContainer } from "@/components/layout/page-container"
import { PageHeader } from "@/components/layout/page-header"
import { Pagination } from "@/components/pagination"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import {
  Empty,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from "@/components/ui/empty"
import { loadArticles, loadCategories } from "@/features/blog/server/blog"
import {
  readPageParam,
  readSearchParam,
  readSlugParam,
} from "@/libs/list-params"
import { ArticleCard } from "./article-card"
import { CategoryFilter } from "./category-filter"

interface BlogPageProps {
  searchParams: Promise<{
    page?: string | string[]
    q?: string | string[]
    category?: string | string[]
  }>
}

export async function generateMetadata(): Promise<Metadata> {
  const t = await getTranslations("blog")
  return { title: t("title"), description: t("description") }
}

export default async function BlogPage({ searchParams }: BlogPageProps) {
  const [params, t] = await Promise.all([searchParams, getTranslations("blog")])

  const page = readPageParam(params.page)
  const search = readSearchParam(params.q)
  const category = readSlugParam(params.category)

  const [articles, categories] = await Promise.all([
    loadArticles({ page, search, category }),
    loadCategories(),
  ])

  return (
    <PageContainer>
      <PageHeader title={t("title")} description={t("description")} />

      <div className="flex flex-col gap-4">
        <ListSearch
          pathname="/blog"
          value={search}
          placeholder={t("searchPlaceholder")}
          label={t("searchLabel")}
          params={{ category }}
        />
        {categories.ok && (
          <CategoryFilter
            categories={categories.data}
            active={category}
            search={search}
          />
        )}
      </div>

      {!articles.ok ? (
        <Alert variant="destructive">
          <AlertTitle>{t("loadError.title")}</AlertTitle>
          <AlertDescription>
            {articles.status === 404
              ? t("loadError.unknownCategory")
              : t("loadError.description")}
          </AlertDescription>
        </Alert>
      ) : articles.data.items.length === 0 ? (
        <Empty className="border">
          <EmptyHeader>
            <EmptyMedia variant="icon">
              <NewspaperIcon />
            </EmptyMedia>
            <EmptyTitle>{t("empty.title")}</EmptyTitle>
            <EmptyDescription>
              {search || category
                ? t("empty.filtered")
                : t("empty.description")}
            </EmptyDescription>
          </EmptyHeader>
        </Empty>
      ) : (
        <>
          <p className="text-sm text-muted-foreground">
            {t("count", { count: articles.data.total })}
          </p>
          <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {articles.data.items.map((article) => (
              <ArticleCard
                key={article.id?.value}
                article={article}
                categories={categories.ok ? categories.data : []}
              />
            ))}
          </ul>
          <Pagination
            page={articles.data.page}
            pages={articles.data.pages}
            pathname="/blog"
            params={{ q: search, category }}
          />
        </>
      )}
    </PageContainer>
  )
}
