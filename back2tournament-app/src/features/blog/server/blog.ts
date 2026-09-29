import "server-only"

import { createApiClient, getServerApiClient } from "@/libs/api/client"
import { loadApiResult } from "@/libs/api/load"
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
  const client = await getServerApiClient()
  return loadApiResult(client.GET("/api/categories/"))
}

export async function loadArticles({
  page = 1,
  search = "",
  category = "",
  limit = ARTICLES_PER_PAGE,
  status = "published",
}: ArticlesQuery = {}) {
  const client = await getServerApiClient()
  const query = {
    page,
    limit,
    ...(search ? { q: search } : {}),
    ...(category ? { category } : {}),
  }
  if (status === "published") {
    return loadApiResult(client.GET("/api/articles/", { params: { query } }))
  }
  return loadApiResult(
    client.GET("/api/editor/articles/", {
      params: { query: { ...query, status } },
    })
  )
}

export async function loadArticle(
  articleId: string,
  { drafts = false }: { drafts?: boolean } = {}
) {
  const client = await getServerApiClient()
  const params = { params: { path: { id: articleId } } }
  return loadApiResult(
    drafts
      ? client.GET("/api/editor/articles/{id}", params)
      : client.GET("/api/articles/{id}", params)
  )
}

const SITEMAP_PAGE_SIZE = 50
const SITEMAP_MAX_PAGES = 10

export async function loadPublicArticles(): Promise<ArticleSummary[]> {
  const client = createApiClient()
  const articles: ArticleSummary[] = []

  for (let page = 1; page <= SITEMAP_MAX_PAGES; page++) {
    const loaded = await loadApiResult(
      client.GET("/api/articles/", {
        params: { query: { page, limit: SITEMAP_PAGE_SIZE } },
      })
    )
    if (!loaded.ok) break

    articles.push(...loaded.data.items)
    if (page >= loaded.data.pages) break
  }

  return articles
}
