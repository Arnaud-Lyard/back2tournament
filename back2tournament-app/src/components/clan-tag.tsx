import { Badge } from "@/components/ui/badge"
import { cn } from "@/libs/utils"

export function ClanTag({
  tag,
  dissolvedLabel,
  className,
}: {
  tag: string | null | undefined
  dissolvedLabel?: string
  className?: string
}) {
  if (!tag) return null

  return (
    <Badge
      variant={dissolvedLabel ? "outline" : "secondary"}
      className={cn("shrink-0 font-mono", className)}
    >
      {tag}
      {dissolvedLabel && (
        <span className="font-sans font-normal text-muted-foreground">
          {dissolvedLabel}
        </span>
      )}
    </Badge>
  )
}
