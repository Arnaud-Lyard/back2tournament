import { ApiError, parseApiErrorCode } from "./errors"

interface FetchJsonOptions {
  method?: "GET" | "POST" | "PATCH" | "DELETE"
  body?: unknown
}

/**
 * Calls one of this app's own Route Handlers. A non-2xx answer throws an
 * ApiError carrying the status, so callers can tell a refusal (403) from a
 * missing resource (404) or an unreachable backend (502). The backend's own
 * wording goes to the console only: the UI words errors itself, translated
 * (see useApiErrorMessage).
 */
export async function fetchJson<T>(
  input: string,
  { method = "GET", body }: FetchJsonOptions = {}
): Promise<T> {
  // A form (an upload) goes as it is: the browser sets its multipart boundary.
  const form = body instanceof FormData
  const response = await fetch(input, {
    method,
    headers:
      body === undefined || form
        ? undefined
        : { "Content-Type": "application/json" },
    body: body === undefined ? undefined : form ? body : JSON.stringify(body),
  })

  if (!response.ok) {
    const error = await readApiError(response)
    console.error(
      `[api] ${method} ${input} → ${error.status}: ${error.message}`
    )
    throw error
  }

  return (await response.json()) as T
}

async function readApiError(response: Response): Promise<ApiError> {
  let message = `Request failed with status ${response.status}`
  let code: unknown
  try {
    const body = (await response.json()) as {
      message?: unknown
      code?: unknown
    }
    if (typeof body.message === "string") message = body.message
    code = body.code
  } catch {
    // Not a JSON body: keep the generic message above.
  }
  return new ApiError(message, response.status, parseApiErrorCode(code))
}
