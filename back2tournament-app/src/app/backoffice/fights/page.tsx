import type { Metadata } from "next"
import Link from "next/link"
import { getTranslations } from "next-intl/server"
import { PageHeader } from "@/components/layout/page-header"
import { ListSearch } from "@/components/list-search"
import { Pagination } from "@/components/pagination"
import { requirePermission } from "@/features/auth/rbac/require"
import {
  FIGHT_STATUS_FILTERS,
  readFightStatusFilter,
} from "@/features/fights/lib/arbitration"
import { loadFight, loadFights } from "@/features/fights/server/fights"
import { loadGames } from "@/features/games/server/games"
import { listHref, readPageParam, readSearchParam } from "@/libs/list-params"
import { readUuidParam } from "@/libs/search-params"
import { cn } from "@/libs/utils"
import { LoadError } from "../load-error"
import { FightPanel } from "./fight-panel"
import { FightsList } from "./fights-list"

const PATHNAME = "/backoffice/fights"

interface BackofficeFightsPageProps {
  searchParams: Promise<{
    status?: string | string[]
    q?: string | string[]
    page?: string | string[]
    fightId?: string | string[]
  }>
}

export async function generateMetadata(): Promise<Metadata> {
  const t = await getTranslations("backoffice.fights")
  return { title: t("title") }
}

export default async function BackofficeFightsPage({
  searchParams,
}: BackofficeFightsPageProps) {
  const [, query, t] = await Promise.all([
    requirePermission("fight:manage"),
    searchParams,
    getTranslations("backoffice.fights"),
  ])
  const status = readFightStatusFilter(query.status)
  const search = readSearchParam(query.q)
  const page = readPageParam(query.page)
  const selected = readUuidParam(query.fightId)
  const selectedId = selected.kind === "valid" ? selected.id : undefined

  const [fights, games, fight] = await Promise.all([
    loadFights({ status, search, page }),
    loadGames(),
    selectedId ? loadFight(selectedId) : undefined,
  ])
  const gameList = games.ok ? games.data : []
  const filters = {
    status: status === "reporting" ? undefined : status,
    q: search,
  }

  return (
    <>
      <PageHeader title={t("title")} description={t("description")} />

      <div className="flex flex-wrap items-center gap-3">
        <nav
          aria-label={t("filters.label")}
          className="flex w-fit flex-wrap items-center gap-1 rounded-lg bg-muted p-1"
        >
          {FIGHT_STATUS_FILTERS.map((filter) => (
            <Link
              key={filter}
              href={listHref(PATHNAME, {
                ...filters,
                status: filter === "reporting" ? undefined : filter,
              })}
              aria-current={filter === status ? "page" : undefined}
              className={cn(
                "rounded-md px-3 py-1 text-sm font-medium transition-colors",
                filter === status
                  ? "bg-background text-foreground shadow-sm"
                  : "text-muted-foreground hover:text-foreground"
              )}
            >
              {t(`filters.${filter}`)}
            </Link>
          ))}
        </nav>
        <ListSearch
          pathname={PATHNAME}
          value={search}
          placeholder={t("searchPlaceholder")}
          label={t("searchLabel")}
          params={{ status: filters.status }}
          className="min-w-64 flex-1"
        />
      </div>

      <div className="grid grid-cols-1 items-start gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
        <div className="flex min-w-0 flex-col gap-6">
          {fights.ok ? (
            <>
              <FightsList
                fights={fights.data.items}
                games={gameList}
                status={status}
                params={{ ...filters, page }}
                selectedId={selectedId}
              />
              <Pagination
                page={fights.data.page}
                pages={fights.data.pages}
                pathname={PATHNAME}
                params={{ ...filters, fightId: selectedId }}
              />
            </>
          ) : (
            <LoadError />
          )}
        </div>
        {selectedId && fight && (
          <FightPanel
            fightId={selectedId}
            fight={fight}
            gameTitle={
              fight.ok
                ? gameList.find(
                    (game) => game.id?.value === fight.data.game?.value
                  )?.title
                : undefined
            }
          />
        )}
      </div>
    </>
  )
}
