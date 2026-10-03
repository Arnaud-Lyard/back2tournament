import { describe, expect, it, vi } from "vitest"
import { loadApiResult } from "./load"

vi.mock("server-only", () => ({}))

type ClanResponse =
  | { data: { name: string }; status: 200 }
  | { data: { error: string }; status: 404 }

describe("loadApiResult", () => {
  it("hands over the data of a success", async () => {
    const call: Promise<ClanResponse> = Promise.resolve({
      data: { name: "Rivals" },
      status: 200,
    })

    expect(await loadApiResult(call)).toEqual({
      ok: true,
      data: { name: "Rivals" },
    })
  })

  it("hands over the status of a refusal, never its body", async () => {
    const call: Promise<ClanResponse> = Promise.resolve({
      data: { error: "clan not found" },
      status: 404,
    })

    expect(await loadApiResult(call)).toEqual({ ok: false, status: 404 })
  })

  it("answers 502 when the API cannot be reached", async () => {
    expect(
      await loadApiResult(Promise.reject(new TypeError("fetch failed")))
    ).toEqual({ ok: false, status: 502 })
  })
})
