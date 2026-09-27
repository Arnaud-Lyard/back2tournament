import {
  ArrowLeftIcon,
  FilePenIcon,
  LanguagesIcon,
  MessageSquareIcon,
} from "lucide-react"
import type { Metadata } from "next"
import Link from "next/link"
import { notFound } from "next/navigation"
import { getFormatter, getLocale, getTranslations } from "next-intl/server"
import { PageContainer } from "@/components/layout/page-container"
import { MediaPlaceholder } from "@/components/media-placeholder"
import { PlayerAvatar } from "@/components/player-avatar"
import { ShareButtons } from "@/components/share-buttons"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Badge } from "@/components/ui/badge"
import { buttonVariants } from "@/components/ui/button"
import { Separator } from "@/components/ui/separator"
import { toExcerpt } from "@/features/blog/lib/excerpt"
import { localizeArticle } from "@/features/blog/lib/localize"
import { articleDate, isPublished } from "@/features/blog/lib/publication"
import { loadArticle, loadCategories } from "@/features/blog/server/blog"
import { baseUrl } from "@/features/site/config"
import { readUuidSegment } from "@/libs/search-params"
import { cn } from "@/libs/utils"
import { CommentForm } from "./comment-form"

interface ArticlePageProps {
  params: Promise<{ articleId: string }>
}

async function readArticleId(params: ArticlePageProps["params"]) {
  return readUuidSegment((await params).articleId)
}

export async function generateMetadata({
  params,
}: ArticlePageProps): Promise<Metadata> {
  const articleId = await readArticleId(params)
  if (!articleId) return {}

  const [article, locale] = await Promise.all([
    loadArticle(articleId),
    getLocale(),
  ])
  if (!article.ok) return {}

  const { title, body } = localizeArticle(article.data, locale)
  const description = toExcerpt(body, 160)
  const { authorName } = article.data

  // A draft reaches the editors only: it has nothing to share yet.
  if (!isPublished(article.data)) {
    return { title, robots: { index: false, follow: false } }
  }

  return {
    title,
    description,
    alternates: { canonical: `/blog/${articleId}` },
    ...(authorName ? { authors: [{ name: authorName }] } : {}),
    openGraph: {
      type: "article",
      title,
      description,
      url: `${baseUrl}/blog/${articleId}`,
      publishedTime: article.data.publishedAt ?? undefined,
      ...(authorName ? { authors: [authorName] } : {}),
    },
  }
}

export default async function ArticlePage({ params }: ArticlePageProps) {
  const articleId = await readArticleId(params)
  if (!articleId) notFound()

  const [t, format, locale, article, categories] = await Promise.all([
    getTranslations("blog"),
    getFormatter(),
    getLocale(),
    loadArticle(articleId),
    loadCategories(),
  ])

  if (!article.ok) {
    if (article.status === 404) notFound()

    return (
      <PageContainer>
        <Alert variant="destructive">
          <AlertTitle>{t("loadError.title")}</AlertTitle>
          <AlertDescription>{t("loadError.description")}</AlertDescription>
        </Alert>
      </PageContainer>
    )
  }

  const { authorName, comments = [] } = article.data
  const { title, body, lang, untranslated } = localizeArticle(
    article.data,
    locale
  )
  const published = isPublished(article.data)
  const date = articleDate(article.data)
  const category = categories.ok
    ? categories.data.find(
        (candidate) => candidate.id === article.data.category?.value
      )
    : undefined

  return (
    <PageContainer className="max-w-3xl">
      <Link
        href="/blog"
        className={cn(
          buttonVariants({ variant: "ghost", size: "sm" }),
          "self-start"
        )}
      >
        <ArrowLeftIcon data-icon="inline-start" />
        {t("back")}
      </Link>

      {!published && (
        <Alert>
          <FilePenIcon />
          <AlertTitle>{t("draft.title")}</AlertTitle>
          <AlertDescription>{t("draft.description")}</AlertDescription>
        </Alert>
      )}

      {untranslated && (
        <Alert>
          <LanguagesIcon />
          <AlertTitle>{t("untranslated.title")}</AlertTitle>
          <AlertDescription>{t("untranslated.description")}</AlertDescription>
        </Alert>
      )}

      <article className="flex flex-col gap-6">
        <header className="flex flex-col gap-3">
          <div className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
            {category && (
              <Badge
                variant="secondary"
                render={<Link href={`/blog?category=${category.slug}`} />}
              >
                {category.name}
              </Badge>
            )}
            {date && (
              <time dateTime={date}>
                {format.dateTime(new Date(date), { dateStyle: "long" })}
              </time>
            )}
            {authorName && <span>{t("byAuthor", { author: authorName })}</span>}
          </div>
          <h1
            lang={lang}
            className="font-heading text-3xl font-semibold tracking-tight text-balance"
          >
            {title}
          </h1>
        </header>

        <MediaPlaceholder className="rounded-xl" />

        <div
          lang={lang}
          className="text-base leading-relaxed whitespace-pre-line"
        >
          {body}
        </div>

        {published && (
          <>
            <Separator />

            <ShareButtons url={`${baseUrl}/blog/${articleId}`} title={title} />
          </>
        )}
      </article>

      <section className="flex flex-col gap-4" aria-labelledby="comments-title">
        <h2
          id="comments-title"
          className="flex items-center gap-2 font-heading text-xl font-semibold"
        >
          <MessageSquareIcon className="size-5" />
          {t("comments.title", { count: comments.length })}
        </h2>

        {comments.length > 0 && (
          <ul className="flex flex-col gap-4">
            {comments.map((comment, index) => {
              const commenter = comment.authorName ?? t("comments.anonymous")

              return (
                <li
                  key={comment.id?.value ?? index}
                  className="flex flex-col gap-2 rounded-lg border p-4"
                >
                  <div className="flex flex-wrap items-center gap-x-2 gap-y-1">
                    <PlayerAvatar battletag={commenter} size="sm" />
                    <span className="text-sm font-medium">{commenter}</span>
                    {comment.createdAt && (
                      <time
                        dateTime={comment.createdAt}
                        className="text-xs text-muted-foreground"
                      >
                        {format.dateTime(new Date(comment.createdAt), {
                          dateStyle: "medium",
                          timeStyle: "short",
                        })}
                      </time>
                    )}
                  </div>
                  <p className="whitespace-pre-line">{comment.message}</p>
                </li>
              )
            })}
          </ul>
        )}

        {published ? (
          <CommentForm articleId={articleId} />
        ) : (
          <p className="text-sm text-muted-foreground">
            {t("comments.closed")}
          </p>
        )}
      </section>
    </PageContainer>
  )
}
