"use client"

import { MoonIcon, SunIcon } from "lucide-react"
import { useTranslations } from "next-intl"
import { useTheme } from "next-themes"
import { Button } from "@/components/ui/button"
import { Kbd } from "@/components/ui/kbd"
import {
  Tooltip,
  TooltipContent,
  TooltipTrigger,
} from "@/components/ui/tooltip"

/**
 * Switches between the light and dark themes. The icon follows the `.dark`
 * class next-themes sets before hydration, so the server markup never has to
 * guess the theme.
 */
export function ThemeToggle() {
  const t = useTranslations("theme")
  const { resolvedTheme, setTheme } = useTheme()

  return (
    <Tooltip>
      <TooltipTrigger
        render={
          <Button
            variant="ghost"
            size="icon"
            onClick={() =>
              setTheme(resolvedTheme === "dark" ? "light" : "dark")
            }
          />
        }
      >
        <SunIcon className="dark:hidden" />
        <MoonIcon className="hidden dark:block" />
        <span className="sr-only">{t("toggle")}</span>
      </TooltipTrigger>
      <TooltipContent>
        {t("toggle")}
        <Kbd>D</Kbd>
      </TooltipContent>
    </Tooltip>
  )
}
