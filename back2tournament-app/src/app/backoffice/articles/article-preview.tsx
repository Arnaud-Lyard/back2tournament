import { getFormatter, getTranslations } from "next-intl/server"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { Separator } from "@/components/ui/separator"
import { loadArticle, loadArticleComments } from "@/features/blog/server/blog"
import type { Category } from "@/features/blog/types"

interface ArticlePreviewProps {
  articleId: string
  categories: Category[]
}

/** Server Component: reads one article, then its comments, straight from the API. */
export async function ArticlePreview({
  articleId,
  categories,
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
  const categoryId = article.category?.value
  const category = categories.find((candidate) => candidate.id === categoryId)

  return (
    <Card>
      <CardHeader>
        {article.createdAt && (
          <CardDescription>
            {t("publishedAt", {
              date: format.dateTime(new Date(article.createdAt), {
                dateStyle: "long",
                timeStyle: "short",
              }),
            })}
          </CardDescription>
        )}
        <CardTitle>{article.title}</CardTitle>
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
          <dd className="truncate font-mono text-xs leading-5">
            {article.author?.value}
          </dd>
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
                      {comment.createdAt && (
                        <span className="text-xs text-muted-foreground">
                          {format.dateTime(new Date(comment.createdAt), {
                            dateStyle: "medium",
                            timeStyle: "short",
                          })}
                        </span>
                      )}
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
    </Card>
  )
}
