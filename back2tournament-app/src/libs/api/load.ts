import "server-only"

interface ApiCallResult<T> {
  data?: T
  response: Response
}

export type Loaded<T> = { ok: true; data: T } | { ok: false; status: number }

/**
 * Settles an openapi-fetch call for a Server Component: the payload, or the
 * status to word the failure with. An unreachable backend reads as a 502.
 */
export async function loadApiResult<T>(
  call: Promise<ApiCallResult<T>>
): Promise<Loaded<T>> {
  try {
    const { data, response } = await call
    return response.ok && data !== undefined
      ? { ok: true, data }
      : { ok: false, status: response.status }
  } catch {
    return { ok: false, status: 502 }
  }
}
