import { ArrowLeftIcon, CrownIcon, ShieldIcon, UsersIcon } from "lucide-react"
import type { Metadata } from "next"
import Link from "next/link"
import { notFound } from "next/navigation"
import { getTranslations } from "next-intl/server"
import { PageContainer } from "@/components/layout/page-container"
import { PageHeader } from "@/components/layout/page-header"
import { PlayerAvatar } from "@/components/player-avatar"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Badge } from "@/components/ui/badge"
import { buttonVariants } from "@/components/ui/button"
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import {
  Empty,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from "@/components/ui/empty"
import { getCurrentUser } from "@/features/auth/server/get-current-user"
import {
  activeClanIn,
  teamFormats,
  teamsLedBy,
} from "@/features/clans/lib/membership"
import { loadClan, loadMyClans } from "@/features/clans/server/clans"
import type { ClanMember, Team } from "@/features/clans/types"
import { formatLabel } from "@/features/fights/lib/challenge"
import { loadGame } from "@/features/games/server/games"
import { readUuidSegment } from "@/libs/search-params"
import { cn } from "@/libs/utils"
import { ChallengeTeamButton } from "./challenge-team-button"
import { CreateTeamForm } from "./create-team-form"
import { DisbandTeamButton } from "./disband-team-button"
import { MembershipActions } from "./membership-actions"
import { RemoveMemberButton } from "./remove-member-button"

interface ClanPageProps {
  params: Promise<{ gameId: string; clanId: string }>
}

export async function generateMetadata({
  params,
}: ClanPageProps): Promise<Metadata> {
  const clanId = readUuidSegment((await params).clanId)
  if (!clanId) return {}

  const clan = await loadClan(clanId)
  return clan.ok ? { title: `${clan.data.name} [${clan.data.tag}]` } : {}
}

export default async function ClanPage({ params }: ClanPageProps) {
  const { gameId: rawGameId, clanId: rawClanId } = await params
  const gameId = readUuidSegment(rawGameId)
  const clanId = readUuidSegment(rawClanId)
  if (!gameId || !clanId) notFound()

  const [t, clan, game, user, myClans] = await Promise.all([
    getTranslations("clans"),
    loadClan(clanId),
    loadGame(gameId),
    getCurrentUser(),
    loadMyClans(),
  ])

  if (!clan.ok) {
    if (clan.status === 404) notFound()

    return (
      <PageContainer>
        <Alert variant="destructive">
          <AlertTitle>{t("loadError.title")}</AlertTitle>
          <AlertDescription>{t("loadError.description")}</AlertDescription>
        </Alert>
      </PageContainer>
    )
  }

  if (clan.data.game?.value !== gameId) notFound()

  const members = clan.data.members ?? []
  const teams = clan.data.teams ?? []
  const active = members.filter((member) => member.status === "active")
  const invited = members.filter((member) => member.status === "invited")

  const myPlayerId = user?.playersByGame[gameId]?.id
  const mine = members.find((member) => member.player?.id?.value === myPlayerId)
  const isLeader = !!myPlayerId && clan.data.leader?.value === myPlayerId

  // The teams the caller leads in another clan of the game, to challenge these ones with.
  const myOtherClan = myClans.ok
    ? activeClanIn(myClans.data, gameId)
    : undefined
  const myOtherClanId = myOtherClan?.clan.id?.value
  const challengers =
    myOtherClanId && myOtherClanId !== clanId
      ? await loadClan(myOtherClanId).then((loaded) =>
          loadedTeamsLedBy(loaded, myPlayerId)
        )
      : []

  const formats = teamFormats(game.ok ? game.data.teamSizes : [])
  const base = `/games/${encodeURIComponent(gameId)}`

  return (
    <PageContainer>
      <Link
        href={`${base}/clans`}
        className={cn(
          buttonVariants({ variant: "ghost", size: "sm" }),
          "self-start"
        )}
      >
        <ArrowLeftIcon data-icon="inline-start" />
        {t("back")}
      </Link>

      <PageHeader
        title={
          <span className="inline-flex items-center gap-3">
            <Badge variant="secondary" className="font-mono text-base">
              {clan.data.tag}
            </Badge>
            {clan.data.name}
          </span>
        }
        description={t("summary", {
          members: active.length,
          teams: teams.length,
        })}
        actions={
          mine && myPlayerId && !isLeader && mine.status ? (
            <MembershipActions
              clanId={clanId}
              playerId={myPlayerId}
              status={mine.status}
            />
          ) : null
        }
      />

      {mine?.status === "invited" && (
        <Alert>
          <AlertTitle>{t("membership.invitedTitle")}</AlertTitle>
          <AlertDescription>
            {t("membership.invitedDescription")}
          </AlertDescription>
        </Alert>
      )}

      <div className="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)]">
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <UsersIcon className="size-4" />
              {t("members.title")}
            </CardTitle>
            {isLeader && (
              <CardDescription>
                {t.rich("members.inviteHint", {
                  link: (chunks) => (
                    <Link
                      href={`${base}/players`}
                      className="underline underline-offset-4"
                    >
                      {chunks}
                    </Link>
                  ),
                })}
              </CardDescription>
            )}
          </CardHeader>
          <CardContent>
            <ul className="flex flex-col divide-y">
              {[...active, ...invited].map((member) => (
                <MemberRow
                  key={member.id?.value}
                  member={member}
                  clanId={clanId}
                  canRemove={isLeader && member.role !== "leader"}
                  roleLabel={t("members.leader")}
                  invitedLabel={t("members.invited")}
                />
              ))}
            </ul>
          </CardContent>
        </Card>

        <div className="flex flex-col gap-6">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <ShieldIcon className="size-4" />
                {t("teams.title")}
              </CardTitle>
              <CardDescription>{t("teams.description")}</CardDescription>
            </CardHeader>
            <CardContent>
              {teams.length === 0 ? (
                <Empty>
                  <EmptyHeader>
                    <EmptyMedia variant="icon">
                      <ShieldIcon />
                    </EmptyMedia>
                    <EmptyTitle>{t("teams.emptyTitle")}</EmptyTitle>
                    <EmptyDescription>
                      {formats.length === 0
                        ? t("teams.noFormat")
                        : t("teams.emptyDescription")}
                    </EmptyDescription>
                  </EmptyHeader>
                </Empty>
              ) : (
                <ul className="flex flex-col gap-3">
                  {teams.map((team) => (
                    <TeamRow
                      key={team.id?.value}
                      team={team}
                      canDisband={isLeader}
                      challengers={challengers.filter(
                        (mine) => mine.size === team.size
                      )}
                    />
                  ))}
                </ul>
              )}
            </CardContent>
          </Card>

          {isLeader && myPlayerId && formats.length > 0 && (
            <CreateTeamForm
              clanId={clanId}
              formats={formats}
              leaderId={myPlayerId}
              members={active.flatMap((member) => {
                const id = member.player?.id?.value
                return id
                  ? [{ id, battletag: member.player?.battletag ?? id }]
                  : []
              })}
            />
          )}
        </div>
      </div>
    </PageContainer>
  )
}

function loadedTeamsLedBy(
  loaded: Awaited<ReturnType<typeof loadClan>>,
  playerId: string | undefined
): Team[] {
  return loaded.ok ? teamsLedBy(loaded.data, playerId) : []
}

function MemberRow({
  member,
  clanId,
  canRemove,
  roleLabel,
  invitedLabel,
}: {
  member: ClanMember
  clanId: string
  canRemove: boolean
  roleLabel: string
  invitedLabel: string
}) {
  const playerId = member.player?.id?.value
  const battletag = member.player?.battletag ?? "?"

  return (
    <li className="flex items-center justify-between gap-3 py-2">
      <div className="flex min-w-0 items-center gap-2">
        <PlayerAvatar battletag={battletag} size="sm" />
        <span className="truncate text-sm">{battletag}</span>
        {member.role === "leader" && (
          <Badge variant="secondary">
            <CrownIcon data-icon="inline-start" />
            {roleLabel}
          </Badge>
        )}
        {member.status === "invited" && (
          <Badge variant="outline">{invitedLabel}</Badge>
        )}
      </div>
      {canRemove && playerId && (
        <RemoveMemberButton
          clanId={clanId}
          playerId={playerId}
          battletag={battletag}
          invited={member.status === "invited"}
        />
      )}
    </li>
  )
}

function TeamRow({
  team,
  canDisband,
  challengers,
}: {
  team: Team
  canDisband: boolean
  challengers: Team[]
}) {
  const teamId = team.id?.value
  const name = team.name ?? ""

  return (
    <li className="flex flex-col gap-2 rounded-lg border p-3">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <div className="flex items-center gap-2">
          <Badge variant="outline">{formatLabel(team.size)}</Badge>
          <span className="font-medium">{name}</span>
        </div>
        {canDisband && teamId && (
          <DisbandTeamButton teamId={teamId} name={name} />
        )}
      </div>
      <ul className="flex flex-wrap gap-2">
        {(team.players ?? []).map((player) => (
          <li
            key={player.id?.value}
            className="inline-flex items-center gap-1 rounded-md bg-muted px-2 py-1 text-xs"
          >
            {player.id?.value === team.leader?.value && (
              <CrownIcon className="size-3" />
            )}
            {player.battletag}
          </li>
        ))}
      </ul>
      {teamId &&
        challengers.map((mine) =>
          mine.id?.value ? (
            <ChallengeTeamButton
              key={mine.id.value}
              myTeamId={mine.id.value}
              myTeamName={mine.name ?? ""}
              theirTeamId={teamId}
              theirTeamName={name}
            />
          ) : null
        )}
    </li>
  )
}
