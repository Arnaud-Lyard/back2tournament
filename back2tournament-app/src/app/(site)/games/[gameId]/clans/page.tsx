import { ArrowRightIcon, PlusIcon, ShieldIcon, UsersIcon } from "lucide-react"
import type { Metadata } from "next"
import Link from "next/link"
import { notFound } from "next/navigation"
import { getTranslations } from "next-intl/server"
import { PageContainer } from "@/components/layout/page-container"
import { PageHeader } from "@/components/layout/page-header"
import { ListSearch } from "@/components/list-search"
import { Pagination } from "@/components/pagination"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Badge } from "@/components/ui/badge"
import { buttonVariants } from "@/components/ui/button"
import { Card, CardDescription, CardTitle } from "@/components/ui/card"
import {
  Empty,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from "@/components/ui/empty"
import { getCurrentUser } from "@/features/auth/server/get-current-user"
import { activeClanIn } from "@/features/clans/lib/membership"
import { loadGameClans, loadMyClans } from "@/features/clans/server/clans"
import type { ClanSummary } from "@/features/clans/types"
import { readPageParam, readSearchParam } from "@/libs/list-params"
import { readUuidSegment } from "@/libs/search-params"
import { cn } from "@/libs/utils"

interface ClansPageProps {
  params: Promise<{ gameId: string }>
  searchParams: Promise<{ page?: string | string[]; q?: string | string[] }>
}

export async function generateMetadata(): Promise<Metadata> {
  const t = await getTranslations("clans")
  return { title: t("title") }
}

export default async function ClansPage({
  params,
  searchParams,
}: ClansPageProps) {
  const gameId = readUuidSegment((await params).gameId)
  if (!gameId) notFound()

  const query = await searchParams
  const page = readPageParam(query.page)
  const search = readSearchParam(query.q)

  const [t, clans, user, myClans] = await Promise.all([
    getTranslations("clans"),
    loadGameClans(gameId, { page, search }),
    getCurrentUser(),
    loadMyClans(),
  ])

  const base = `/games/${encodeURIComponent(gameId)}/clans`
  const mine = myClans.ok ? activeClanIn(myClans.data, gameId) : undefined
  const myClanId = mine?.clan.id?.value

  return (
    <PageContainer>
      <PageHeader
        title={t("title")}
        description={t("description")}
        actions={
          myClanId ? (
            <Link
              href={`${base}/${encodeURIComponent(myClanId)}`}
              className={cn(buttonVariants({ variant: "outline" }))}
            >
              <ShieldIcon data-icon="inline-start" />
              {t("myClan", { name: mine?.clan.name ?? "" })}
            </Link>
          ) : user?.playersByGame[gameId] ? (
            <Link href={`${base}/new`} className={cn(buttonVariants())}>
              <PlusIcon data-icon="inline-start" />
              {t("found")}
            </Link>
          ) : null
        }
      />

      <ListSearch
        pathname={base}
        value={search}
        placeholder={t("searchPlaceholder")}
        label={t("searchLabel")}
      />

      {!clans.ok ? (
        <Alert variant="destructive">
          <AlertTitle>{t("loadError.title")}</AlertTitle>
          <AlertDescription>{t("loadError.description")}</AlertDescription>
        </Alert>
      ) : clans.data.items.length === 0 ? (
        <Empty className="border">
          <EmptyHeader>
            <EmptyMedia variant="icon">
              <ShieldIcon />
            </EmptyMedia>
            <EmptyTitle>{t("empty.title")}</EmptyTitle>
            <EmptyDescription>
              {search ? t("empty.filtered") : t("empty.description")}
            </EmptyDescription>
          </EmptyHeader>
        </Empty>
      ) : (
        <>
          <p className="text-sm text-muted-foreground">
            {t("count", { count: clans.data.total })}
          </p>
          <ul className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            {clans.data.items.map((clan) => (
              <ClanCard key={clan.id?.value} clan={clan} base={base} />
            ))}
          </ul>
          <Pagination
            page={clans.data.page}
            pages={clans.data.pages}
            pathname={base}
            params={{ q: search }}
          />
        </>
      )}
    </PageContainer>
  )
}

async function ClanCard({ clan, base }: { clan: ClanSummary; base: string }) {
  const id = clan.id?.value
  if (!id) return null

  const t = await getTranslations("clans")

  return (
    <li>
      <Card className="group h-full gap-0 p-0 transition-colors hover:border-primary/50">
        <Link
          href={`${base}/${encodeURIComponent(id)}`}
          className="flex h-full flex-col gap-2 rounded-[inherit] p-4 outline-none focus-visible:ring-3 focus-visible:ring-ring/50"
        >
          <div className="flex items-center gap-2">
            <Badge variant="secondary" className="font-mono">
              {clan.tag}
            </Badge>
            <CardTitle className="truncate">{clan.name}</CardTitle>
          </div>
          <CardDescription className="flex items-center justify-between gap-2">
            <span className="inline-flex items-center gap-1">
              <UsersIcon className="size-4" />
              {t("memberCount", { count: clan.members ?? 0 })}
            </span>
            <span className="inline-flex items-center gap-1 text-primary">
              {t("view")}
              <ArrowRightIcon className="size-4 transition-transform group-hover:translate-x-0.5" />
            </span>
          </CardDescription>
        </Link>
      </Card>
    </li>
  )
}
