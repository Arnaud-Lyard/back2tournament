import { MailCheckIcon, MailXIcon } from "lucide-react"
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
import { createApiClient } from "@/libs/api/client"

interface VerifyEmailPageProps {
  params: Promise<{ token: string }>
}

export default async function VerifyEmailPage({
  params,
}: VerifyEmailPageProps) {
  const { token } = await params
  const t = await getTranslations("auth")

  const client = createApiClient()
  const { error } = await client.GET("/api/verify-email/{token}", {
    params: { path: { token } },
  })

  return (
    <Empty className="max-w-sm border">
      <EmptyHeader>
        <EmptyMedia variant="icon">
          {error ? <MailXIcon /> : <MailCheckIcon />}
        </EmptyMedia>
        <EmptyTitle>
          <h1>{t("verifyEmailTitle")}</h1>
        </EmptyTitle>
        <EmptyDescription>
          {error ? t("verifyEmailError") : t("verifyEmailSuccess")}
        </EmptyDescription>
      </EmptyHeader>
      <EmptyContent>
        <Link href="/login" className={cn(buttonVariants())}>
          {t("submitLogin")}
        </Link>
      </EmptyContent>
    </Empty>
  )
}
