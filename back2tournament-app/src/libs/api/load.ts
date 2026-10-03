import "server-only"

import {
  succeeded,
  type SuccessData,
  type SymfonyResponse,
} from "./symfony-fetch"

export type Loaded<T> = { ok: true; data: T } | { ok: false; status: number }

export async function loadApiResult<TResponse extends SymfonyResponse>(
  call: Promise<TResponse>
): Promise<Loaded<SuccessData<TResponse>>> {
  try {
    const { data, status } = await call
    return succeeded(status) && data !== undefined
      ? { ok: true, data: data as SuccessData<TResponse> }
      : { ok: false, status }
  } catch {
    return { ok: false, status: 502 }
  }
}
