import "server-only"

import { NextResponse } from "next/server"
import type { z } from "zod"
import { toApiError } from "./errors"

interface ApiCallResult<T> {
  data?: T
  error?: unknown
  response: Response
}

/**
 * Relays an openapi-fetch call to the browser: the payload on success,
 * `{ message, code }` with the backend's own status otherwise. `message` is
 * the backend's English wording, for the console only; `code` is set for the
 * few failures the UI must tell apart (see ApiErrorCode). It checks
 * `response.ok` rather than `error`, which openapi-fetch leaves undefined
 * when an error response has an empty body. An unreachable backend is a 502.
 */
export async function relayApiResult<T>(
  call: Promise<ApiCallResult<T>>
): Promise<NextResponse> {
  let result: ApiCallResult<T>
  try {
    result = await call
  } catch {
    return NextResponse.json(
      { message: "Backend unavailable" },
      { status: 502 }
    )
  }

  if (!result.response.ok) {
    const apiError = toApiError(result.response.status, result.error)
    return NextResponse.json(
      { message: apiError.message, code: apiError.code },
      { status: apiError.status }
    )
  }

  return NextResponse.json(result.data ?? null)
}

/** Validates a JSON request body; a body that is not JSON is simply invalid. */
export async function parseRequestBody<TSchema extends z.ZodType>(
  request: Request,
  schema: TSchema
) {
  const body: unknown = await request.json().catch(() => undefined)
  return schema.safeParse(body)
}

export function invalidInput(error: z.ZodError): NextResponse {
  return NextResponse.json(
    { message: error.issues[0]?.message ?? "Invalid input" },
    { status: 400 }
  )
}
