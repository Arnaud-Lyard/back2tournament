import Image from "next/image"
import { MediaPlaceholder } from "@/components/media-placeholder"
import { cn } from "@/libs/utils"

interface StoredImageProps {
  /** Where the image is read from; the placeholder stands in when there is none. */
  src?: string | null
  /** Empty when the image only illustrates the text beside it. */
  alt?: string
  ratio?: "wide" | "square"
  className?: string
  /** Loads it at once rather than when it scrolls into view: the top of a page. */
  eager?: boolean
}

/**
 * An image the API stored, cropped to fill a box of the given ratio. The API
 * already resized and compressed it to WebP, so it is served as it is, straight
 * from the storage, without going through Next's optimizer.
 */
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
