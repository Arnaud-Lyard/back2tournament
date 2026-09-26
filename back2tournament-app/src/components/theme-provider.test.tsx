import { fireEvent, waitFor } from "@testing-library/react"
import { describe, expect, it } from "vitest"
import { renderWithProviders } from "@tests/test-utils"

describe("ThemeProvider hotkey", () => {
  it("toggles the theme with the D key", async () => {
    renderWithProviders(<div />)
    await waitFor(() => expect(document.documentElement).toHaveClass("light"))

    fireEvent.keyDown(window, { key: "d" })

    await waitFor(() => expect(document.documentElement).toHaveClass("dark"))
  })

  it("ignores a keydown without a key, as Chrome's autofill sends", () => {
    renderWithProviders(<div />)
    // A throwing listener does not reach dispatchEvent: it is reported here.
    const errors: unknown[] = []
    const onError = (event: ErrorEvent) => {
      errors.push(event.error)
      event.preventDefault()
    }
    window.addEventListener("error", onError)

    window.dispatchEvent(new Event("keydown"))

    window.removeEventListener("error", onError)
    expect(errors).toEqual([])
  })
})
