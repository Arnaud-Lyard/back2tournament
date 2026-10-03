import "server-only"

import { env } from "@/libs/env"

export interface SymfonyResponse {
  data: unknown
  status: number
}

export type SuccessData<TResponse extends SymfonyResponse> = Extract<
  TResponse,
  { status: 200 | 201 | 202 | 204 }
>["data"]

const NO_BODY = new Set([204, 205, 304])

export function succeeded(status: number): boolean {
  return status >= 200 && status < 300
}

export async function symfonyFetch<T>(
  path: string,
  init: RequestInit
): Promise<T> {
  const response = await fetch(`${env.SYMFONY_API_URL}${path}`, init)
  const text = NO_BODY.has(response.status) ? "" : await response.text()
  const isJson = response.headers.get("content-type")?.includes("json")
  const data: unknown = text && isJson ? JSON.parse(text) : text || undefined

  return { data, status: response.status, headers: response.headers } as T
}
