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
import { getVerifyEmail } from "@/libs/api/generated/authentication"
import { succeeded } from "@/libs/api/symfony-fetch"

interface VerifyEmailPageProps {
  params: Promise<{ token: string }>
}

export default async function VerifyEmailPage({
  params,
}: VerifyEmailPageProps) {
  const { token } = await params
  const t = await getTranslations("auth")

  const { status } = await getVerifyEmail(token)
  const verified = succeeded(status)

  return (
    <Empty className="max-w-sm border">
      <EmptyHeader>
        <EmptyMedia variant="icon">
          {verified ? <MailCheckIcon /> : <MailXIcon />}
        </EmptyMedia>
        <EmptyTitle>
          <h1>{t("verifyEmailTitle")}</h1>
        </EmptyTitle>
        <EmptyDescription>
          {verified ? t("verifyEmailSuccess") : t("verifyEmailError")}
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
