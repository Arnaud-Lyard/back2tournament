import { ArrowLeftIcon } from "lucide-react"
import type { Metadata } from "next"
import Link from "next/link"
import { notFound } from "next/navigation"
import { getTranslations } from "next-intl/server"
import { PageContainer } from "@/components/layout/page-container"
import { PageHeader } from "@/components/layout/page-header"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { buttonVariants } from "@/components/ui/button"
import { requireUser } from "@/features/auth/rbac/require"
import { activeClanIn } from "@/features/clans/lib/membership"
import { loadMyClans } from "@/features/clans/server/clans"
import { readUuidSegment } from "@/libs/search-params"
import { cn } from "@/libs/utils"
import { CreateClanForm } from "./create-clan-form"

export async function generateMetadata(): Promise<Metadata> {
  const t = await getTranslations("clans.create")
  return { title: t("title") }
}

export default async function NewClanPage({
  params,
}: {
  params: Promise<{ gameId: string }>
}) {
  const gameId = readUuidSegment((await params).gameId)
  if (!gameId) notFound()

  const [user, t, myClans] = await Promise.all([
    requireUser(),
    getTranslations("clans"),
    loadMyClans(),
  ])

  const base = `/games/${encodeURIComponent(gameId)}/clans`
  const player = user.playersByGame[gameId]
  const current = myClans.ok ? activeClanIn(myClans.data, gameId) : undefined

  return (
    <PageContainer className="max-w-2xl">
      <Link
        href={base}
        className={cn(
          buttonVariants({ variant: "ghost", size: "sm" }),
          "self-start"
        )}
      >
        <ArrowLeftIcon data-icon="inline-start" />
        {t("back")}
      </Link>
      <PageHeader
        title={t("create.title")}
        description={t("create.description")}
      />
      {!player ? (
        <Alert>
          <AlertTitle>{t("create.noProfile")}</AlertTitle>
          <AlertDescription>
            <Link
              href={`/players/new?gameId=${encodeURIComponent(gameId)}`}
              className="underline underline-offset-4"
            >
              {t("create.createProfile")}
            </Link>
          </AlertDescription>
        </Alert>
      ) : current ? (
        <Alert>
          <AlertTitle>
            {t("create.alreadyMember", { name: current.clan.name ?? "" })}
          </AlertTitle>
          <AlertDescription>
            {t("create.alreadyMemberDescription")}
          </AlertDescription>
        </Alert>
      ) : (
        <CreateClanForm gameId={gameId} />
      )}
    </PageContainer>
  )
}
