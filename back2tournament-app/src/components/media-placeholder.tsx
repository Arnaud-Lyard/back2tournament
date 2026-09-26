import { ImageIcon } from "lucide-react"
import { cn } from "@/libs/utils"

interface MediaPlaceholderProps {
  ratio?: "wide" | "square"
  className?: string
  iconClassName?: string
}

export function MediaPlaceholder({
  ratio = "wide",
  className,
  iconClassName,
}: MediaPlaceholderProps) {
  return (
    <div
      aria-hidden
      className={cn(
        "flex items-center justify-center overflow-hidden bg-gradient-to-br from-muted to-secondary text-muted-foreground/60",
        ratio === "wide" ? "aspect-[16/9]" : "aspect-square",
        className
      )}
    >
      <ImageIcon className={cn("size-8", iconClassName)} />
    </div>
  )
}
