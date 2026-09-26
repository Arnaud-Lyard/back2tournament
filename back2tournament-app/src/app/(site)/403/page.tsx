import { ShieldAlertIcon } from "lucide-react"
import { getTranslations } from "next-intl/server"
import Link from "next/link"
import { buttonVariants } from "@/components/ui/button"
import { cn } from "@/libs/utils"
import {
  Empty,
  EmptyContent,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from "@/components/ui/empty"

export default async function ForbiddenPage() {
  const t = await getTranslations("errors")

  return (
    <div className="flex flex-1 items-center justify-center px-4 py-12">
      <Empty className="max-w-md border">
        <EmptyHeader>
          <EmptyMedia variant="icon">
            <ShieldAlertIcon />
          </EmptyMedia>
          <EmptyTitle>
            <h1>{t("forbiddenTitle")}</h1>
          </EmptyTitle>
          <EmptyDescription>{t("forbiddenDescription")}</EmptyDescription>
        </EmptyHeader>
        <EmptyContent>
          <Link href="/" className={cn(buttonVariants())}>
            {t("backHome")}
          </Link>
        </EmptyContent>
      </Empty>
    </div>
  )
}
