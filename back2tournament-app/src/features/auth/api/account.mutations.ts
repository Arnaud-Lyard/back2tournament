import { fetchJson } from "@/libs/api/fetch-json"

export function deleteAccount(password: string): Promise<{ deleted: boolean }> {
  return fetchJson<{ deleted: boolean }>("/api/users/me/deletion", {
    method: "POST",
    body: { password },
  })
}
