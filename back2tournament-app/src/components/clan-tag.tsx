import { Badge } from "@/components/ui/badge"
import { cn } from "@/libs/utils"

/**
 * The tag of the clan a player profile or a team plays for, shown before its
 * name in lists. Renders nothing for a profile in no clan.
 */
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
