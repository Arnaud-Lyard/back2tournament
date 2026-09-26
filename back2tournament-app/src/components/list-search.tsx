import { SearchIcon, XIcon } from "lucide-react"
import Link from "next/link"
import { getTranslations } from "next-intl/server"
import { Button, buttonVariants } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { listHref } from "@/libs/list-params"
import { cn } from "@/libs/utils"

interface ListSearchProps {
  pathname: string
  value: string
  placeholder: string
  label: string
  params?: Record<string, string | undefined>
  className?: string
}

export async function ListSearch({
  pathname,
  value,
  placeholder,
  label,
  params = {},
  className,
}: ListSearchProps) {
  const t = await getTranslations("search")

  return (
    <form
      action={pathname}
      method="get"
      role="search"
      className={cn("flex w-full max-w-sm items-center gap-2", className)}
    >
      {Object.entries(params).map(([name, carried]) =>
        carried ? (
          <input key={name} type="hidden" name={name} value={carried} />
        ) : null
      )}
      <div className="relative flex-1">
        <SearchIcon
          aria-hidden
          className="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
        />
        <Input
          type="search"
          name="q"
          defaultValue={value}
          placeholder={placeholder}
          aria-label={label}
          className="pl-8"
        />
      </div>
      <Button type="submit" variant="outline">
        {t("submit")}
      </Button>
      {value && (
        <Link
          href={listHref(pathname, params)}
          aria-label={t("clear")}
          className={cn(buttonVariants({ variant: "ghost", size: "icon" }))}
        >
          <XIcon />
        </Link>
      )}
    </form>
  )
}
