import { ExternalLinkIcon, PencilIcon } from "lucide-react"
import Link from "next/link"
import { getFormatter, getTranslations } from "next-intl/server"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { buttonVariants } from "@/components/ui/button"
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { Separator } from "@/components/ui/separator"
import { isPublished } from "@/features/blog/lib/publication"
import { loadArticle, loadArticleComments } from "@/features/blog/server/blog"
import type { Category } from "@/features/blog/types"
import { cn } from "@/libs/utils"
import { ArticleStatusBadge } from "./article-status-badge"
import { ArticleStatusButton } from "./article-status-button"

interface ArticlePreviewProps {
  articleId: string
  categories: Category[]
  /** Where the "Edit" button leads; none when the caller may not edit. */
  editHref?: string
  canPublish: boolean
}

/** Server Component: reads one article, then its comments, straight from the API. */
export async function ArticlePreview({
  articleId,
  categories,
  editHref,
  canPublish,
}: ArticlePreviewProps) {
  const [t, format, loaded, comments] = await Promise.all([
    getTranslations("backoffice.articles.preview"),
    getFormatter(),
    loadArticle(articleId),
    loadArticleComments(articleId),
  ])

  if (!loaded.ok) {
    const reason = loaded.status === 404 ? "notFound" : "loadError"
    return (
      <Alert variant="destructive">
        <AlertTitle>{t(`${reason}.title`)}</AlertTitle>
        <AlertDescription>{t(`${reason}.description`)}</AlertDescription>
      </Alert>
    )
  }

  const article = loaded.data
  const published = isPublished(article)
  const categoryId = article.category?.value
  const category = categories.find((candidate) => candidate.id === categoryId)
  const dated = published ? article.publishedAt : article.createdAt

  return (
    <Card>
      <CardHeader>
        {dated && (
          <CardDescription>
            {t(published ? "publishedAt" : "writtenAt", {
              date: format.dateTime(new Date(dated), {
                dateStyle: "long",
                timeStyle: "short",
              }),
            })}
          </CardDescription>
        )}
        <CardTitle>{article.title}</CardTitle>
        <CardAction>
          <ArticleStatusBadge article={article} />
        </CardAction>
      </CardHeader>
      <CardContent className="flex flex-col gap-4">
        <p className="whitespace-pre-line">{article.body}</p>
        <dl className="grid grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-1 text-muted-foreground">
          <dt>{t("category")}</dt>
          <dd className="truncate">
            {category?.name ?? (
              <span className="font-mono text-xs leading-5">{categoryId}</span>
            )}
          </dd>
          <dt>{t("author")}</dt>
          <dd className="truncate">{article.authorName ?? t("noAuthor")}</dd>
        </dl>
        <Separator />
        <section className="flex flex-col gap-3">
          {comments.ok ? (
            <>
              <h3 className="font-medium">
                {t("comments", { count: comments.data.length })}
              </h3>
              {comments.data.length > 0 && (
                <ul className="flex flex-col gap-3">
                  {comments.data.map((comment, index) => (
                    <li
                      key={comment.id?.value ?? index}
                      className="flex flex-col gap-0.5"
                    >
                      <span className="text-xs text-muted-foreground">
                        <span className="font-medium text-foreground">
                          {comment.authorName ?? t("anonymous")}
                        </span>
                        {comment.createdAt && (
                          <>
                            {" · "}
                            {format.dateTime(new Date(comment.createdAt), {
                              dateStyle: "medium",
                              timeStyle: "short",
                            })}
                          </>
                        )}
                      </span>
                      <p>{comment.message}</p>
                    </li>
                  ))}
                </ul>
              )}
            </>
          ) : (
            <p className="text-muted-foreground">{t("commentsError")}</p>
          )}
        </section>
      </CardContent>
      {(editHref || canPublish || published) && (
        <CardFooter className="flex-wrap gap-2">
          {canPublish && article.status && (
            <ArticleStatusButton
              articleId={articleId}
              status={article.status}
            />
          )}
          {editHref && (
            <Link
              href={editHref}
              scroll={false}
              className={cn(buttonVariants({ variant: "outline" }))}
            >
              <PencilIcon data-icon="inline-start" />
              {t("edit")}
            </Link>
          )}
          {published && (
            <Link
              href={`/blog/${encodeURIComponent(articleId)}`}
              className={cn(buttonVariants({ variant: "ghost" }))}
            >
              <ExternalLinkIcon data-icon="inline-start" />
              {t("viewOnBlog")}
            </Link>
          )}
        </CardFooter>
      )}
    </Card>
  )
}
