import type { Metadata } from "next"
import { getTranslations } from "next-intl/server"
import { ImagePicker } from "@/components/image-picker"
import { PageHeader } from "@/components/layout/page-header"
import { Pagination } from "@/components/pagination"
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { hasPermission } from "@/features/auth/rbac/can"
import { requirePermission } from "@/features/auth/rbac/require"
import {
  loadArticle,
  loadArticles,
  loadCategories,
} from "@/features/blog/server/blog"
import { listHref, readPageParam } from "@/libs/list-params"
import { readUuidParam } from "@/libs/search-params"
import { LoadError } from "../load-error"
import { ArticlePreview } from "./article-preview"
import { ArticlesList } from "./articles-list"
import { CreateArticleForm } from "./create-article-form"
import { EditArticleForm } from "./edit-article-form"

const PATHNAME = "/backoffice/articles"

interface BackofficeArticlesPageProps {
  searchParams: Promise<{
    articleId?: string | string[]
    page?: string | string[]
    edit?: string | string[]
  }>
}

export async function generateMetadata(): Promise<Metadata> {
  const t = await getTranslations("backoffice.articles")
  return { title: t("title") }
}

export default async function BackofficeArticlesPage({
  searchParams,
}: BackofficeArticlesPageProps) {
  const [user, query, t] = await Promise.all([
    requirePermission("article:create"),
    searchParams,
    getTranslations("backoffice.articles"),
  ])
  const page = readPageParam(query.page)
  const selected = readUuidParam(query.articleId)
  const selectedId = selected.kind === "valid" ? selected.id : undefined
  const canEdit = hasPermission(user.permissions, "article:edit")
  const canPublish = hasPermission(user.permissions, "article:publish")
  const editing = !!selectedId && canEdit && [query.edit].flat().includes("1")

  const [articles, categories, edited] = await Promise.all([
    loadArticles({ page, status: "all" }),
    loadCategories(),
    editing ? loadArticle(selectedId) : undefined,
  ])
  const categoryList = categories.ok ? categories.data : []
  const previewHref = listHref(PATHNAME, { page, articleId: selectedId })

  return (
    <>
      <PageHeader title={t("title")} description={t("description")} />
      <div className="grid grid-cols-1 items-start gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)]">
        {categories.ok ? (
          <CreateArticleForm categories={categoryList} />
        ) : (
          <LoadError />
        )}
        <div className="flex min-w-0 flex-col gap-6">
          {selectedId &&
            (edited?.ok ? (
              <>
                <Card>
                  <CardHeader>
                    <CardTitle>{t("cover.title")}</CardTitle>
                    <CardDescription>{t("cover.description")}</CardDescription>
                  </CardHeader>
                  <CardContent>
                    <ImagePicker
                      endpoint={`/api/articles/${encodeURIComponent(selectedId)}/image`}
                      image={edited.data.image}
                      name={edited.data.title ?? ""}
                    />
                  </CardContent>
                </Card>
                <EditArticleForm
                  key={selectedId}
                  articleId={selectedId}
                  article={edited.data}
                  categories={categoryList}
                  previewHref={previewHref}
                />
              </>
            ) : (
              <ArticlePreview
                articleId={selectedId}
                categories={categoryList}
                editHref={
                  canEdit
                    ? listHref(PATHNAME, {
                        page,
                        articleId: selectedId,
                        edit: 1,
                      })
                    : undefined
                }
                canPublish={canPublish}
                canIllustrate={canEdit}
              />
            ))}
          {articles.ok ? (
            <>
              <ArticlesList
                articles={articles.data.items}
                categories={categoryList}
                page={articles.data.page}
                selectedId={selectedId}
              />
              <Pagination
                page={articles.data.page}
                pages={articles.data.pages}
                pathname={PATHNAME}
                params={{ articleId: selectedId }}
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
