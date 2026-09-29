import { getTranslations } from "next-intl/server"
import { Badge } from "@/components/ui/badge"
import type { FightSummary } from "@/features/fights/types"

const VARIANT = {
  pending: "outline",
  reporting: "default",
  finished: "secondary",
} as const

export async function FightStatusBadge({
  fight,
}: {
  fight: Pick<FightSummary, "status">
}) {
  const t = await getTranslations("backoffice.fights")
  const status = fight.status ?? "pending"

  return <Badge variant={VARIANT[status]}>{t(`status.${status}`)}</Badge>
}
