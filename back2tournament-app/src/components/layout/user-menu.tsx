"use client"

import {
  ChevronDownIcon,
  Gamepad2Icon,
  LogOutIcon,
  ShieldIcon,
  SwordsIcon,
  TrophyIcon,
  UsersRoundIcon,
} from "lucide-react"
import Link from "next/link"
import { useTranslations } from "next-intl"
import { Avatar, AvatarFallback } from "@/components/ui/avatar"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { useAuth } from "@/features/auth/hooks/use-auth"

export function UserMenu() {
  const t = useTranslations("nav")
  const tRoles = useTranslations("roles")
  const { user, hasPermission, signOut } = useAuth()

  if (!user) return null

  return (
    <DropdownMenu>
      <DropdownMenuTrigger
        aria-label={t("accountMenu", { username: user.username })}
        render={<Button variant="ghost" />}
      >
        <Avatar size="sm">
          <AvatarFallback>
            {user.username.slice(0, 2).toUpperCase()}
          </AvatarFallback>
        </Avatar>
        <span className="hidden max-w-32 truncate sm:inline">
          {user.username}
        </span>
        <ChevronDownIcon data-icon="inline-end" />
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="w-56">
        <DropdownMenuGroup>
          <DropdownMenuLabel className="flex items-center justify-between gap-2">
            <span className="truncate">{user.username}</span>
            <Badge variant="secondary">{tRoles(user.role)}</Badge>
          </DropdownMenuLabel>
        </DropdownMenuGroup>
        <DropdownMenuSeparator />
        <DropdownMenuGroup>
          <DropdownMenuItem render={<Link href="/players" />}>
            <Gamepad2Icon />
            {t("playerProfile")}
          </DropdownMenuItem>
          <DropdownMenuItem render={<Link href="/challenges" />}>
            <SwordsIcon />
            {t("challenges")}
          </DropdownMenuItem>
          <DropdownMenuItem render={<Link href="/clans" />}>
            <UsersRoundIcon />
            {t("myClans")}
          </DropdownMenuItem>
          <DropdownMenuItem render={<Link href="/tournaments/new" />}>
            <TrophyIcon />
            {t("organizeTournament")}
          </DropdownMenuItem>
          {hasPermission("backoffice:access") && (
            <DropdownMenuItem render={<Link href="/backoffice" />}>
              <ShieldIcon />
              {t("backoffice")}
            </DropdownMenuItem>
          )}
        </DropdownMenuGroup>
        <DropdownMenuSeparator />
        <DropdownMenuGroup>
          <DropdownMenuItem
            variant="destructive"
            onClick={() => void signOut()}
          >
            <LogOutIcon />
            {t("logout")}
          </DropdownMenuItem>
        </DropdownMenuGroup>
      </DropdownMenuContent>
    </DropdownMenu>
  )
}
