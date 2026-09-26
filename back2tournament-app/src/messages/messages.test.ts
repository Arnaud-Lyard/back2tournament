import { describe, expect, it } from "vitest"
import en from "./en.json"
import fr from "./fr.json"

function keysOf(messages: object, prefix = ""): string[] {
  return Object.entries(messages).flatMap(([key, value]) =>
    typeof value === "object" && value !== null
      ? keysOf(value, `${prefix}${key}.`)
      : [`${prefix}${key}`]
  )
}

// Only fr.json types the `t()` keys (src/global.d.ts): this keeps the other
// locales from silently missing one.
describe("messages", () => {
  it("defines the same keys in every locale", () => {
    expect(keysOf(en).sort()).toEqual(keysOf(fr).sort())
  })
})
