import "server-only"

import {
  getArticle,
  getArticleList,
  getEditorArticle,
  getEditorArticleList,
} from "@/libs/api/generated/article"
import { getCategoryList } from "@/libs/api/generated/category"
import { loadApiResult } from "@/libs/api/load"
import { withSession } from "@/libs/api/session"
import {
  ARTICLES_PER_PAGE,
  type ArticleStatusFilter,
  type ArticleSummary,
} from "../types"

interface ArticlesQuery {
  page?: number
  search?: string
  category?: string
  limit?: number
  status?: ArticleStatusFilter
}

export async function loadCategories() {
  return loadApiResult(getCategoryList(await withSession()))
}

export async function loadArticles({
  page = 1,
  search = "",
  category = "",
  limit = ARTICLES_PER_PAGE,
  status = "published",
}: ArticlesQuery = {}) {
  const session = await withSession()
  const query = {
    page,
    limit,
    q: search || undefined,
    category: category || undefined,
  }
  if (status === "published") {
    return loadApiResult(getArticleList(query, session))
  }
  return loadApiResult(getEditorArticleList({ ...query, status }, session))
}

export async function loadArticle(
  articleId: string,
  { drafts = false }: { drafts?: boolean } = {}
) {
  const session = await withSession()
  return loadApiResult(
    drafts
      ? getEditorArticle(articleId, session)
      : getArticle(articleId, session)
  )
}

const SITEMAP_PAGE_SIZE = 50
const SITEMAP_MAX_PAGES = 10

export async function loadPublicArticles(): Promise<ArticleSummary[]> {
  const articles: ArticleSummary[] = []

  for (let page = 1; page <= SITEMAP_MAX_PAGES; page++) {
    const loaded = await loadApiResult(
      getArticleList({ page, limit: SITEMAP_PAGE_SIZE })
    )
    if (!loaded.ok) break

    articles.push(...loaded.data.items)
    if (page >= loaded.data.pages) break
  }

  return articles
}
