import {
  ArrowLeftIcon,
  CalendarIcon,
  TrophyIcon,
  UsersIcon,
} from "lucide-react"
import type { Metadata } from "next"
import Link from "next/link"
import { notFound } from "next/navigation"
import { getFormatter, getTranslations } from "next-intl/server"
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
import { getCurrentUser } from "@/features/auth/server/get-current-user"
import type { AuthUser } from "@/features/auth/types"
import { activeClanIn, teamsLedBy } from "@/features/clans/lib/membership"
import { loadClan, loadMyClans } from "@/features/clans/server/clans"
import { formatLabel } from "@/features/fights/lib/challenge"
import { loadGame } from "@/features/games/server/games"
import { STATUS_VARIANT } from "@/features/tournaments/lib/status"
import { loadTournament } from "@/features/tournaments/server/tournaments"
import type { TournamentDetail } from "@/features/tournaments/types"
import { readUuidSegment } from "@/libs/search-params"
import { cn } from "@/libs/utils"
import { Bracket } from "./bracket"
import { OrganizerActions } from "./organizer-actions"
import { RegisterActions, type RegistrationOption } from "./register-actions"
import { WithdrawButton } from "./withdraw-button"

interface TournamentPageProps {
  params: Promise<{ tournamentId: string }>
}

export async function generateMetadata({
  params,
}: TournamentPageProps): Promise<Metadata> {
  const tournamentId = readUuidSegment((await params).tournamentId)
  if (!tournamentId) return {}

  const tournament = await loadTournament(tournamentId)
  return tournament.ok ? { title: tournament.data.name ?? "" } : {}
}

export default async function TournamentPage({ params }: TournamentPageProps) {
  const tournamentId = readUuidSegment((await params).tournamentId)
  if (!tournamentId) notFound()

  const [t, format, tournament, user] = await Promise.all([
    getTranslations("tournaments"),
    getFormatter(),
    loadTournament(tournamentId),
    getCurrentUser(),
  ])

  if (!tournament.ok) {
    if (tournament.status === 404) notFound()

    return (
      <PageContainer>
        <Alert variant="destructive">
          <AlertTitle>{t("loadError.title")}</AlertTitle>
          <AlertDescription>{t("loadError.description")}</AlertDescription>
        </Alert>
      </PageContainer>
    )
  }

  const data = tournament.data
  const gameId = data.game?.value ?? ""
  const game = gameId ? await loadGame(gameId) : null
  const participants = data.participants ?? []
  const status = data.status ?? "upcoming"
  const isOrganizer = !!user?.id && user.id === data.organizer?.value

  // What the caller may register: their profile in 1v1, the teams they lead otherwise.
  const mine = await registrationOptions(data, user)
  const registeredIds = new Set(
    participants.map((participant) => participant.reference?.value)
  )
  const options = mine.filter((option) => !registeredIds.has(option.id))
  const myReferences = new Set(mine.map((option) => option.id))
  const full = participants.length >= (data.capacity ?? 0)

  return (
    <PageContainer>
      <Link
        href="/tournaments"
        className={cn(
          buttonVariants({ variant: "ghost", size: "sm" }),
          "self-start"
        )}
      >
        <ArrowLeftIcon data-icon="inline-start" />
        {t("back")}
      </Link>

      <PageHeader
        title={data.name}
        description={
          <span className="flex flex-wrap items-center gap-x-4 gap-y-1">
            <span>{game?.ok ? game.data.title : t("unknownGame")}</span>
            <span className="inline-flex items-center gap-1">
              <UsersIcon className="size-4" />
              {t("seats", {
                count: participants.length,
                capacity: data.capacity ?? 0,
              })}
            </span>
            {data.startsAt && (
              <span className="inline-flex items-center gap-1">
                <CalendarIcon className="size-4" />
                {format.dateTime(new Date(data.startsAt), {
                  dateStyle: "long",
                  timeStyle: "short",
                })}
              </span>
            )}
          </span>
        }
        actions={
          isOrganizer ? (
            <OrganizerActions
              tournamentId={tournamentId}
              status={status}
              participantCount={participants.length}
            />
          ) : null
        }
      />

      <div className="flex flex-wrap items-center gap-2">
        <Badge variant={STATUS_VARIANT[status]}>{t(`status.${status}`)}</Badge>
        <Badge variant="outline">{formatLabel(data.teamSize)}</Badge>
        {isOrganizer && <Badge variant="secondary">{t("youOrganize")}</Badge>}
      </div>

      {data.winner && (
        <Alert>
          <TrophyIcon />
          <AlertTitle>
            {t("winner", { name: data.winner.name ?? "?" })}
          </AlertTitle>
        </Alert>
      )}

      {status === "upcoming" && user && (
        <Card size="sm">
          <CardContent className="flex flex-wrap items-center justify-between gap-3">
            <p className="text-sm text-muted-foreground">
              {full
                ? t("registration.full")
                : options.length > 0
                  ? t("registration.open")
                  : mine.length > 0
                    ? t("registration.done")
                    : (data.teamSize ?? 1) > 1
                      ? t("registration.noTeam", {
                          format: formatLabel(data.teamSize),
                        })
                      : t("registration.noProfile")}
            </p>
            {!full && options.length > 0 && (
              <RegisterActions tournamentId={tournamentId} options={options} />
            )}
          </CardContent>
        </Card>
      )}

      <div className="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,2fr)]">
        <Card>
          <CardHeader>
            <CardTitle>{t("participants.title")}</CardTitle>
            <CardDescription>{t("participants.description")}</CardDescription>
          </CardHeader>
          <CardContent>
            {participants.length === 0 ? (
              <p className="text-sm text-muted-foreground">
                {t("participants.empty")}
              </p>
            ) : (
              <ol className="flex flex-col divide-y">
                {participants.map((participant) => {
                  const participantId = participant.id?.value
                  const name = participant.name ?? "?"
                  const canWithdraw =
                    status === "upcoming" &&
                    !!participantId &&
                    (isOrganizer ||
                      myReferences.has(participant.reference?.value ?? ""))

                  return (
                    <li
                      key={participantId}
                      className="flex items-center justify-between gap-3 py-2"
                    >
                      <div className="flex min-w-0 items-center gap-2">
                        <span className="w-6 shrink-0 text-right font-mono text-xs text-muted-foreground">
                          {participant.seed}
                        </span>
                        <PlayerAvatar battletag={name} size="sm" />
                        <span className="truncate text-sm">{name}</span>
                      </div>
                      {canWithdraw && participantId && (
                        <WithdrawButton
                          tournamentId={tournamentId}
                          participantId={participantId}
                          name={name}
                        />
                      )}
                    </li>
                  )
                })}
              </ol>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>{t("bracket.title")}</CardTitle>
            <CardDescription>
              {(data.matchups ?? []).length === 0
                ? t("bracket.notDrawn")
                : t("bracket.description")}
            </CardDescription>
          </CardHeader>
          {(data.matchups ?? []).length > 0 && (
            <CardContent>
              <Bracket
                matchups={data.matchups ?? []}
                rounds={data.rounds ?? 0}
              />
            </CardContent>
          )}
        </Card>
      </div>
    </PageContainer>
  )
}

/** The caller's profile in the game for 1v1, or the teams they lead in the tournament's format. */
async function registrationOptions(
  tournament: TournamentDetail,
  user: AuthUser | null
): Promise<RegistrationOption[]> {
  const gameId = tournament.game?.value
  const player = gameId ? user?.playersByGame[gameId] : undefined
  if (!gameId || !player) return []

  if ((tournament.teamSize ?? 1) === 1) {
    return [{ kind: "player", id: player.id, name: player.battletag }]
  }

  const myClans = await loadMyClans()
  const clanId = myClans.ok
    ? activeClanIn(myClans.data, gameId)?.clan.id?.value
    : undefined
  if (!clanId) return []

  const clan = await loadClan(clanId)
  if (!clan.ok) return []

  return teamsLedBy(clan.data, player.id)
    .filter((team) => team.size === tournament.teamSize)
    .flatMap((team) =>
      team.id?.value
        ? [{ kind: "team" as const, id: team.id.value, name: team.name ?? "" }]
        : []
    )
}
