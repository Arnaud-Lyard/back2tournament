"use client"

import { SendIcon, Undo2Icon } from "lucide-react"
import { useRouter } from "next/navigation"
import { useTranslations } from "next-intl"
import { Button } from "@/components/ui/button"
import { Spinner } from "@/components/ui/spinner"
import { toast } from "@/components/ui/toast"
import { useChangeArticleStatus } from "@/features/blog/hooks/use-change-article-status"
import type { ArticleStatus } from "@/features/blog/types"
import { useApiErrorMessage } from "@/hooks/use-api-error-message"

/** Publishes a draft, the caller becoming its author, or takes an article back to draft. */
export function ArticleStatusButton({
  articleId,
  status,
}: {
  articleId: string
  status: ArticleStatus
}) {
  const t = useTranslations("backoffice.articles.publication")
  const router = useRouter()
  const describeError = useApiErrorMessage()
  const changeStatus = useChangeArticleStatus()
  const publish = status !== "published"

  function onClick() {
    changeStatus.mutate(
      { id: articleId, status: publish ? "published" : "draft" },
      {
        onSuccess: () => {
          toast.add({
            type: "success",
            title: publish ? t("published") : t("unpublished"),
            description: publish
              ? t("publishedDescription")
              : t("unpublishedDescription"),
          })
          router.refresh()
        },
        onError: (error) => {
          toast.add({
            type: "error",
            title: publish ? t("publishError") : t("unpublishError"),
            description: describeError(error, {
              403: t("errors.forbidden"),
              404: t("errors.gone"),
              409: t("errors.changed"),
            }),
          })
        },
      }
    )
  }

  const Icon = publish ? SendIcon : Undo2Icon

  return (
    <Button
      variant={publish ? "default" : "outline"}
      onClick={onClick}
      disabled={changeStatus.isPending}
    >
      {changeStatus.isPending ? (
        <Spinner data-icon="inline-start" />
      ) : (
        <Icon data-icon="inline-start" />
      )}
      {publish ? t("publish") : t("unpublish")}
    </Button>
  )
}
