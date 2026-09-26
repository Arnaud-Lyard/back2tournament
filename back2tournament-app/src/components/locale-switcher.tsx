"use client"

import { useLocale, useTranslations } from "next-intl"
import { useTransition } from "react"
import { LocaleFlag } from "@/components/locale-flag"
import { Button } from "@/components/ui/button"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuRadioGroup,
  DropdownMenuRadioItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { setLocale } from "@/features/i18n/actions"
import { supportedLocales } from "@/features/site/config"

/** Each language is named in itself, so a visitor finds theirs whatever the page is in. */
const LANGUAGE_NAMES: Record<string, string> = {
  fr: "Français",
  en: "English",
}

/**
 * Shows the current language's flag. Picking another one stores it in the
 * locale cookie, which also decides the language of the verification email
 * sent on sign-up.
 */
export function LocaleSwitcher() {
  const t = useTranslations("locale")
  const locale = useLocale()
  const [isPending, startTransition] = useTransition()

  return (
    <DropdownMenu>
      <DropdownMenuTrigger
        aria-label={t("change")}
        disabled={isPending}
        render={<Button variant="ghost" size="icon" />}
      >
        <LocaleFlag locale={locale} />
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="w-40">
        <DropdownMenuRadioGroup
          value={locale}
          onValueChange={(value: string) => {
            if (value === locale) return
            startTransition(() => setLocale(value))
          }}
        >
          {supportedLocales.map((code) => (
            <DropdownMenuRadioItem
              key={code}
              value={code}
              lang={code}
              closeOnClick
            >
              <LocaleFlag locale={code} />
              {LANGUAGE_NAMES[code] ?? code.toUpperCase()}
            </DropdownMenuRadioItem>
          ))}
        </DropdownMenuRadioGroup>
      </DropdownMenuContent>
    </DropdownMenu>
  )
}
