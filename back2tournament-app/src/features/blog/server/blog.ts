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
  return loadApiResult(
    client.GET("/api/articles/", {
      params: {
        query: {
          page,
          limit,
          status,
          ...(search ? { q: search } : {}),
          ...(category ? { category } : {}),
        },
      },
    })
  )
}

export async function loadArticle(articleId: string) {
  const client = await getServerApiClient()
  return loadApiResult(
    client.GET("/api/articles/{id}", { params: { path: { id: articleId } } })
  )
}

export async function loadArticleComments(articleId: string) {
  const client = await getServerApiClient()
  return loadApiResult(
    client.GET("/api/articles/{id}/comments", {
      params: { path: { id: articleId } },
    })
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
