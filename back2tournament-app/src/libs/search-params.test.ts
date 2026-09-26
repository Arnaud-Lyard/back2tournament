import { describe, expect, it } from "vitest"
import { readUuidParam } from "./search-params"

const GAME_ID = "0a1b2c3d-4e5f-4a6b-8c7d-9e0f1a2b3c4d"

describe("readUuidParam", () => {
  it("reports a missing or blank param", () => {
    expect(readUuidParam(undefined)).toEqual({ kind: "missing" })
    expect(readUuidParam("  ")).toEqual({ kind: "missing" })
  })

  it("normalises a valid identifier", () => {
    expect(readUuidParam(` ${GAME_ID.toUpperCase()} `)).toEqual({
      kind: "valid",
      id: GAME_ID,
    })
  })

  it("keeps the raw value of an invalid one, to show it back", () => {
    expect(readUuidParam("oops")).toEqual({ kind: "invalid", raw: "oops" })
  })

  it("reads the first value of a repeated param", () => {
    expect(readUuidParam([GAME_ID, "oops"])).toEqual({
      kind: "valid",
      id: GAME_ID,
    })
  })
})
