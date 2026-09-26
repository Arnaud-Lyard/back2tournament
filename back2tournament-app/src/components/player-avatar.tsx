import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"

interface PlayerAvatarProps {
  battletag: string
  src?: string
  size?: "default" | "sm" | "lg"
  className?: string
}

export function PlayerAvatar({
  battletag,
  src,
  size = "default",
  className,
}: PlayerAvatarProps) {
  return (
    <Avatar size={size} className={className}>
      {src && <AvatarImage src={src} alt="" />}
      <AvatarFallback>{initialsOf(battletag)}</AvatarFallback>
    </Avatar>
  )
}

function initialsOf(battletag: string): string {
  const trimmed = battletag.trim()
  return trimmed ? trimmed.slice(0, 2).toUpperCase() : "?"
}
