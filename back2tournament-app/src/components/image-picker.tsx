"use client"

import { CheckIcon, ImageUpIcon, Trash2Icon, XIcon } from "lucide-react"
import Image from "next/image"
import { useRouter } from "next/navigation"
import { useTranslations } from "next-intl"
import { useRef, useState, useTransition, type ChangeEvent } from "react"
import { MediaPlaceholder } from "@/components/media-placeholder"
import { Button } from "@/components/ui/button"
import { Spinner } from "@/components/ui/spinner"
import { toast } from "@/components/ui/toast"
import {
  useRemoveImage,
  useUploadImage,
} from "@/features/images/hooks/use-image-mutations"
import { IMAGE_TYPES, imageFileProblem } from "@/features/images/lib/image-file"
import { useApiErrorMessage } from "@/hooks/use-api-error-message"
import { cn } from "@/libs/utils"

type Shape = "wide" | "square" | "round"

interface ImagePickerProps {
  endpoint: string
  image?: string | null
  name: string
  shape?: Shape
  compact?: boolean
}

const FRAMES: Record<Shape, string> = {
  wide: "w-full rounded-lg",
  square: "size-24 rounded-lg",
  round: "size-24 rounded-full",
}

export function ImagePicker({
  endpoint,
  image,
  name,
  shape = "wide",
  compact = false,
}: ImagePickerProps) {
  const t = useTranslations("images")
  const tCommon = useTranslations("common")
  const router = useRouter()
  const describeError = useApiErrorMessage()
  const upload = useUploadImage()
  const remove = useRemoveImage()
  const [refreshing, startRefresh] = useTransition()
  const [confirming, setConfirming] = useState(false)
  const [action, setAction] = useState<"upload" | "remove">()
  const input = useRef<HTMLInputElement>(null)
  const busy = upload.isPending || remove.isPending || refreshing
  const uploading = busy && action === "upload"
  const removing = busy && action === "remove"

  function onChosen(event: ChangeEvent<HTMLInputElement>) {
    const file = event.target.files?.[0]
    event.target.value = ""
    if (!file) return

    const problem = imageFileProblem(file)
    if (problem) {
      toast.add({
        type: "error",
        title: t("uploadError"),
        description: t(`problems.${problem}`),
      })
      return
    }

    setConfirming(false)
    setAction("upload")
    upload.mutate(
      { endpoint, image: file },
      {
        onSuccess: () => {
          toast.add({ type: "success", title: t("uploaded") })
          startRefresh(() => router.refresh())
        },
        onError: (error) => {
          toast.add({
            type: "error",
            title: t("uploadError"),
            description: describeError(error, {
              400: t("problems.unreadable"),
            }),
          })
        },
      }
    )
  }

  function onRemove() {
    setAction("remove")
    remove.mutate(
      { endpoint },
      {
        onSuccess: () => {
          setConfirming(false)
          toast.add({ type: "success", title: t("removed") })
          startRefresh(() => router.refresh())
        },
        onError: (error) => {
          toast.add({
            type: "error",
            title: t("removeError"),
            description: describeError(error),
          })
        },
      }
    )
  }

  const frame = compact
    ? cn("size-10", shape === "round" ? "rounded-full" : "rounded-md")
    : FRAMES[shape]
  const preview = image ? (
    <div
      className={cn(
        "relative shrink-0 overflow-hidden bg-muted",
        shape === "wide" && !compact ? "aspect-[16/9]" : "aspect-square",
        frame
      )}
    >
      <Image
        src={image}
        alt={t("alt", { name })}
        fill
        unoptimized
        className="object-cover"
      />
    </div>
  ) : (
    <MediaPlaceholder
      ratio={shape === "wide" && !compact ? "wide" : "square"}
      className={cn("shrink-0", frame)}
      iconClassName={compact ? "size-4" : undefined}
    />
  )
  const fileInput = (
    <input
      ref={input}
      type="file"
      accept={IMAGE_TYPES.join(",")}
      onChange={onChosen}
      className="hidden"
      tabIndex={-1}
      aria-hidden
    />
  )

  if (compact) {
    return (
      <div className="flex items-center gap-1">
        {preview}
        {fileInput}
        <Button
          variant="ghost"
          size="icon-sm"
          onClick={() => input.current?.click()}
          disabled={busy}
          aria-label={t(image ? "changeLabel" : "chooseLabel", { name })}
        >
          {uploading ? <Spinner /> : <ImageUpIcon />}
        </Button>
        {image &&
          (confirming ? (
            <>
              <Button
                variant="destructive"
                size="icon-sm"
                onClick={onRemove}
                disabled={busy}
                aria-label={t("confirmRemoveLabel", { name })}
              >
                {removing ? <Spinner /> : <CheckIcon />}
              </Button>
              <Button
                variant="ghost"
                size="icon-sm"
                onClick={() => setConfirming(false)}
                disabled={busy}
                aria-label={tCommon("cancel")}
              >
                <XIcon />
              </Button>
            </>
          ) : (
            <Button
              variant="ghost"
              size="icon-sm"
              onClick={() => setConfirming(true)}
              disabled={busy}
              aria-label={t("removeLabel", { name })}
            >
              <Trash2Icon />
            </Button>
          ))}
      </div>
    )
  }

  return (
    <div
      className={cn(
        "flex gap-4",
        shape === "wide" ? "flex-col" : "flex-col sm:flex-row sm:items-center"
      )}
    >
      {preview}
      <div className="flex flex-col gap-2">
        {fileInput}
        <div className="flex flex-wrap items-center gap-2">
          <Button
            variant="outline"
            onClick={() => input.current?.click()}
            disabled={busy}
          >
            {uploading ? (
              <Spinner data-icon="inline-start" />
            ) : (
              <ImageUpIcon data-icon="inline-start" />
            )}
            {t(image ? "change" : "choose")}
          </Button>
          {image &&
            (confirming ? (
              <>
                <span className="text-xs text-muted-foreground">
                  {t("confirmRemove")}
                </span>
                <Button
                  variant="destructive"
                  onClick={onRemove}
                  disabled={busy}
                >
                  {removing && <Spinner data-icon="inline-start" />}
                  {tCommon("confirm")}
                </Button>
                <Button
                  variant="ghost"
                  onClick={() => setConfirming(false)}
                  disabled={busy}
                >
                  {tCommon("cancel")}
                </Button>
              </>
            ) : (
              <Button
                variant="ghost"
                onClick={() => setConfirming(true)}
                disabled={busy}
              >
                <Trash2Icon data-icon="inline-start" />
                {t("remove")}
              </Button>
            ))}
        </div>
        <p className="text-xs text-muted-foreground">{t("hint")}</p>
      </div>
    </div>
  )
}
