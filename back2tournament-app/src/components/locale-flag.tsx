import { useId, type ComponentType } from "react"
import { cn } from "@/libs/utils"

function FlagFr() {
  return (
    <svg
      viewBox="0 0 3 2"
      preserveAspectRatio="xMidYMid slice"
      className="size-full"
    >
      <path fill="#002654" d="M0 0h1v2H0z" />
      <path fill="#fff" d="M1 0h1v2H1z" />
      <path fill="#ce1126" d="M2 0h1v2H2z" />
    </svg>
  )
}

function FlagGb() {
  // Two Union Jacks on one page (trigger and menu) need distinct clip ids.
  const clipId = `gb-${useId().replace(/[^\w-]/g, "")}`

  return (
    <svg
      viewBox="0 0 60 30"
      preserveAspectRatio="xMidYMid slice"
      className="size-full"
    >
      <clipPath id={clipId}>
        <path d="M30 15h30v15zv15h-30zh-30v-15zv-15h30z" />
      </clipPath>
      <path fill="#012169" d="M0 0h60v30H0z" />
      <path stroke="#fff" strokeWidth="6" d="M0 0l60 30m0-30L0 30" />
      <path
        stroke="#c8102e"
        strokeWidth="4"
        clipPath={`url(#${clipId})`}
        d="M0 0l60 30m0-30L0 30"
      />
      <path stroke="#fff" strokeWidth="10" d="M30 0v30M0 15h60" />
      <path stroke="#c8102e" strokeWidth="6" d="M30 0v30M0 15h60" />
    </svg>
  )
}

const FLAGS: Record<string, ComponentType> = {
  fr: FlagFr,
  en: FlagGb,
}

interface LocaleFlagProps {
  locale: string
  className?: string
}

/**
 * Drawn inline rather than as an emoji: Windows renders flag emojis as two
 * bare letters.
 */
export function LocaleFlag({ locale, className }: LocaleFlagProps) {
  const Flag = FLAGS[locale]

  return (
    <span
      aria-hidden="true"
      className={cn(
        "inline-flex h-3.5 w-5 shrink-0 items-center justify-center overflow-hidden rounded-[3px] ring-1 ring-foreground/15",
        !Flag && "bg-muted text-[0.55rem] font-semibold uppercase",
        className
      )}
    >
      {Flag ? <Flag /> : locale}
    </span>
  )
}
