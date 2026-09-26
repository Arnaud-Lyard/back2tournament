import { redirect } from "next/navigation"

export default async function GamePage({
  params,
}: {
  params: Promise<{ gameId: string }>
}) {
  const { gameId } = await params
  redirect(`/games/${encodeURIComponent(gameId)}/players`)
}
