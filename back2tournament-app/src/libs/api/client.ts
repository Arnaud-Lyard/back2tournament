import "server-only"

import createClient from "openapi-fetch"
import { cookies } from "next/headers"
import { env } from "@/libs/env"
import { AUTH_COOKIE_NAME } from "@/features/auth/lib/cookie"
import type { paths } from "./schema"

export function createApiClient(bearerToken?: string) {
  const client = createClient<paths>({ baseUrl: env.SYMFONY_API_URL })

  if (bearerToken) {
    client.use({
      onRequest({ request }) {
        request.headers.set("Authorization", `Bearer ${bearerToken}`)
        return request
      },
    })
  }

  return client
}

export async function getServerApiClient() {
  const token = (await cookies()).get(AUTH_COOKIE_NAME)?.value
  return createApiClient(token)
}
