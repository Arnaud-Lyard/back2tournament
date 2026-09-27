import "server-only"

import { NextResponse } from "next/server"
import { uuid } from "@/libs/validation"

/** A backend identifier read from a Route Handler's path, or undefined when it is not one. */
export async function readIdParam<TKey extends string>(
  params: Promise<Record<TKey, string>>,
  key: TKey
): Promise<string | undefined> {
  const parsed = uuid().safeParse((await params)[key])
  return parsed.success ? parsed.data : undefined
}

export function unknownResource(message: string): NextResponse {
  return NextResponse.json({ message }, { status: 404 })
}
