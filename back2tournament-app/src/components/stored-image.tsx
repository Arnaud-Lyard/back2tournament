import Image from "next/image"
import { MediaPlaceholder } from "@/components/media-placeholder"
import { cn } from "@/libs/utils"

interface StoredImageProps {
  src?: string | null
  alt?: string
  ratio?: "wide" | "square"
  className?: string
  eager?: boolean
}

export function StoredImage({
  src,
  alt = "",
  ratio = "wide",
  className,
  eager = false,
}: StoredImageProps) {
  if (!src) return <MediaPlaceholder ratio={ratio} className={className} />

  return (
    <div
      className={cn(
        "relative overflow-hidden bg-muted",
        ratio === "wide" ? "aspect-[16/9]" : "aspect-square",
        className
      )}
    >
      <Image
        src={src}
        alt={alt}
        fill
        unoptimized
        loading={eager ? "eager" : "lazy"}
        className="object-cover"
      />
    </div>
  )
}
