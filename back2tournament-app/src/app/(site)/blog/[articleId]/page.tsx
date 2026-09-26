import { ArrowLeftIcon, MessageSquareIcon } from "lucide-react"
import type { Metadata } from "next"
import Link from "next/link"
import { notFound } from "next/navigation"
import { getFormatter, getTranslations } from "next-intl/server"
import { PageContainer } from "@/components/layout/page-container"
import { MediaPlaceholder } from "@/components/media-placeholder"
import { ShareButtons } from "@/components/share-buttons"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Badge } from "@/components/ui/badge"
import { buttonVariants } from "@/components/ui/button"
import { Separator } from "@/components/ui/separator"
import { toExcerpt } from "@/features/blog/lib/excerpt"
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

  const article = await loadArticle(articleId)
  if (!article.ok) return {}

  const title = article.data.title ?? ""
  const description = toExcerpt(article.data.body, 160)

  return {
    title,
    description,
    alternates: { canonical: `/blog/${articleId}` },
    openGraph: {
      type: "article",
      title,
      description,
      url: `${baseUrl}/blog/${articleId}`,
      publishedTime: article.data.createdAt,
    },
  }
}

export default async function ArticlePage({ params }: ArticlePageProps) {
  const articleId = await readArticleId(params)
  if (!articleId) notFound()

  const [t, format, article, categories] = await Promise.all([
    getTranslations("blog"),
    getFormatter(),
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

  const { title, body, createdAt, comments = [] } = article.data
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
            {createdAt && (
              <time dateTime={createdAt}>
                {format.dateTime(new Date(createdAt), { dateStyle: "long" })}
              </time>
            )}
          </div>
          <h1 className="font-heading text-3xl font-semibold tracking-tight text-balance">
            {title}
          </h1>
        </header>

        <MediaPlaceholder className="rounded-xl" />

        <div className="text-base leading-relaxed whitespace-pre-line">
          {body}
        </div>

        <Separator />

        <ShareButtons
          url={`${baseUrl}/blog/${articleId}`}
          title={title ?? ""}
        />
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
            {comments.map((comment, index) => (
              <li
                key={comment.id?.value ?? index}
                className="flex flex-col gap-1 rounded-lg border p-4"
              >
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
                <p className="whitespace-pre-line">{comment.message}</p>
              </li>
            ))}
          </ul>
        )}

        <CommentForm articleId={articleId} />
      </section>
    </PageContainer>
  )
}
