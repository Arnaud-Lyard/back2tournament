import type { Metadata } from "next"
import { getTranslations } from "next-intl/server"
import { PageHeader } from "@/components/layout/page-header"
import { Pagination } from "@/components/pagination"
import { requirePermission } from "@/features/auth/rbac/require"
import { loadArticles, loadCategories } from "@/features/blog/server/blog"
import { readPageParam } from "@/libs/list-params"
import { readUuidParam } from "@/libs/search-params"
import { LoadError } from "../load-error"
import { ArticlePreview } from "./article-preview"
import { ArticlesList } from "./articles-list"
import { CreateArticleForm } from "./create-article-form"

interface BackofficeArticlesPageProps {
  searchParams: Promise<{
    articleId?: string | string[]
    page?: string | string[]
  }>
}

export async function generateMetadata(): Promise<Metadata> {
  const t = await getTranslations("backoffice.articles")
  return { title: t("title") }
}

export default async function BackofficeArticlesPage({
  searchParams,
}: BackofficeArticlesPageProps) {
  const [, query, t] = await Promise.all([
    requirePermission("article:create"),
    searchParams,
    getTranslations("backoffice.articles"),
  ])
  const page = readPageParam(query.page)
  const [articles, categories] = await Promise.all([
    loadArticles({ page }),
    loadCategories(),
  ])
  const selected = readUuidParam(query.articleId)
  const categoryList = categories.ok ? categories.data : []

  return (
    <>
      <PageHeader title={t("title")} description={t("description")} />
      <div className="grid items-start gap-6 xl:grid-cols-2">
        {categories.ok ? (
          <CreateArticleForm categories={categoryList} />
        ) : (
          <LoadError />
        )}
        <div className="flex flex-col gap-6">
          {selected.kind === "valid" && (
            <ArticlePreview articleId={selected.id} categories={categoryList} />
          )}
          {articles.ok ? (
            <>
              <ArticlesList
                articles={articles.data.items}
                categories={categoryList}
                selectedId={selected.kind === "valid" ? selected.id : undefined}
              />
              <Pagination
                page={articles.data.page}
                pages={articles.data.pages}
                pathname="/backoffice/articles"
                params={{
                  articleId:
                    selected.kind === "valid" ? selected.id : undefined,
                }}
              />
            </>
          ) : (
            <LoadError />
          )}
        </div>
      </div>
    </>
  )
}
