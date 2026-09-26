"use client"

import { UserPlusIcon } from "lucide-react"
import { useRouter } from "next/navigation"
import { useTranslations } from "next-intl"
import { useState } from "react"
import { Button } from "@/components/ui/button"
import { NativeSelect, NativeSelectOption } from "@/components/ui/native-select"
import { Spinner } from "@/components/ui/spinner"
import { toast } from "@/components/ui/toast"
import { useRegisterParticipant } from "@/features/tournaments/hooks/use-register-participant"
import { useApiErrorMessage } from "@/hooks/use-api-error-message"

export interface RegistrationOption {
  kind: "player" | "team"
  id: string
  name: string
}

interface RegisterActionsProps {
  tournamentId: string
  /** The caller's profile for a 1v1 tournament, or the teams they lead in its format. */
  options: RegistrationOption[]
}

export function RegisterActions({
  tournamentId,
  options,
}: RegisterActionsProps) {
  const t = useTranslations("tournaments.registration")
  const router = useRouter()
  const describeError = useApiErrorMessage()
  const register = useRegisterParticipant()
  const [selected, setSelected] = useState(options[0]?.id ?? "")

  const option = options.find((candidate) => candidate.id === selected)

  function onRegister() {
    if (!option) return

    register.mutate(
      option.kind === "player"
        ? { tournamentId, player: option.id }
        : { tournamentId, team: option.id },
      {
        onSuccess: () => {
          toast.add({
            type: "success",
            title: t("registered", { name: option.name }),
          })
          router.refresh()
        },
        onError: (error) => {
          toast.add({
            type: "error",
            title: t("error"),
            description: describeError(error, {
              403: t("errors.notYours"),
              409: t("errors.closed"),
            }),
          })
        },
      }
    )
  }

  return (
    <div className="flex flex-wrap items-center gap-2">
      {options.length > 1 && (
        <NativeSelect
          aria-label={t("chooseTeam")}
          value={selected}
          onChange={(event) => setSelected(event.target.value)}
        >
          {options.map((candidate) => (
            <NativeSelectOption key={candidate.id} value={candidate.id}>
              {candidate.name}
            </NativeSelectOption>
          ))}
        </NativeSelect>
      )}
      <Button onClick={onRegister} disabled={!option || register.isPending}>
        {register.isPending ? (
          <Spinner data-icon="inline-start" />
        ) : (
          <UserPlusIcon data-icon="inline-start" />
        )}
        {options.length > 1
          ? t("register")
          : t("registerAs", { name: option?.name ?? "" })}
      </Button>
    </div>
  )
}
