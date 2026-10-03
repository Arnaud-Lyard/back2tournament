import "server-only"

import { NextResponse } from "next/server"
import type { z } from "zod"
import { toApiError } from "./errors"
import { succeeded, type SymfonyResponse } from "./symfony-fetch"

export async function relayApiResult(
  call: Promise<SymfonyResponse>
): Promise<NextResponse> {
  let result: SymfonyResponse
  try {
    result = await call
  } catch {
    return NextResponse.json(
      { message: "Backend unavailable" },
      { status: 502 }
    )
  }

  if (!succeeded(result.status)) {
    const apiError = toApiError(result.status, result.data)
    return NextResponse.json(
      { message: apiError.message, code: apiError.code },
      { status: apiError.status }
    )
  }

  return NextResponse.json(result.data ?? null)
}

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
