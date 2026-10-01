import { getServerApiClient } from "@/libs/api/client"
import { readIdParam, unknownResource } from "@/libs/api/route-params"
import {
  invalidInput,
  parseRequestBody,
  relayApiResult,
} from "@/libs/api/route-response"
import { passwordConfirmationSchema } from "@/features/auth/schemas/password-confirmation.schema"

interface ClanContext {
  params: Promise<{ id: string }>
}

export async function POST(request: Request, { params }: ClanContext) {
  const id = await readIdParam(params, "id")
  if (!id) return unknownResource("Unknown clan")

  const parsed = await parseRequestBody(request, passwordConfirmationSchema)
  if (!parsed.success) return invalidInput(parsed.error)

  const client = await getServerApiClient()
  return relayApiResult(
    client.POST("/api/user/clans/{id}/dissolution", {
      params: { path: { id } },
      body: parsed.data,
    })
  )
}
