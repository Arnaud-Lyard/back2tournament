import { screen, waitFor } from "@testing-library/react"
import userEvent from "@testing-library/user-event"
import { describe, expect, it } from "vitest"
import { renderWithProviders } from "@tests/test-utils"
import { ThemeToggle } from "./theme-toggle"

describe("ThemeToggle", () => {
  it("switches between the light and dark themes", async () => {
    const user = userEvent.setup()
    renderWithProviders(<ThemeToggle />)
    const toggle = screen.getByRole("button", { name: "Toggle theme" })

    await waitFor(() => expect(document.documentElement).toHaveClass("light"))

    await user.click(toggle)
    await waitFor(() => expect(document.documentElement).toHaveClass("dark"))

    await user.click(toggle)
    await waitFor(() =>
      expect(document.documentElement).not.toHaveClass("dark")
    )
  })
})
