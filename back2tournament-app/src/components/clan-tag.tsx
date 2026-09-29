import { Badge } from "@/components/ui/badge"
import { cn } from "@/libs/utils"

export function ClanTag({
  tag,
  className,
}: {
  tag: string | null | undefined
  className?: string
}) {
  if (!tag) return null

  return (
    <Badge variant="secondary" className={cn("shrink-0 font-mono", className)}>
      {tag}
    </Badge>
  )
}
