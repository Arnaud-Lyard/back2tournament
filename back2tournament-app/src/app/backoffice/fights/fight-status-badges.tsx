import { getTranslations } from "next-intl/server"
import { Badge } from "@/components/ui/badge"
import type { FightSummary } from "@/features/fights/types"

const VARIANT = {
  pending: "outline",
  reporting: "default",
  finished: "secondary",
} as const

/** Where a fight stands, and whether an administrator settled it. */
export async function FightStatusBadges({
  fight,
}: {
  fight: Pick<FightSummary, "status" | "arbitrated">
}) {
  const t = await getTranslations("backoffice.fights")
  const status = fight.status ?? "pending"

  return (
    <span className="flex flex-wrap items-center gap-1">
      <Badge variant={VARIANT[status]}>{t(`status.${status}`)}</Badge>
      {fight.arbitrated && <Badge variant="outline">{t("arbitrated")}</Badge>}
    </span>
  )
}
