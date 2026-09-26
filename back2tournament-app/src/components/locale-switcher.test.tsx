import { screen, waitFor } from "@testing-library/react"
import userEvent from "@testing-library/user-event"
import { afterEach, describe, expect, it, vi } from "vitest"
import { setLocale } from "@/features/i18n/actions"
import { renderWithProviders } from "@tests/test-utils"
import { LocaleSwitcher } from "./locale-switcher"

vi.mock("@/features/i18n/actions", () => ({ setLocale: vi.fn() }))

afterEach(() => {
  vi.mocked(setLocale).mockClear()
})

async function openSwitcher() {
  const user = userEvent.setup()
  renderWithProviders(<LocaleSwitcher />)
  await user.click(screen.getByRole("button", { name: "Change language" }))
  return user
}

describe("LocaleSwitcher", () => {
  it("names every language in itself and ticks the current one", async () => {
    await openSwitcher()

    expect(
      await screen.findByRole("menuitemradio", { name: "English" })
    ).toHaveAttribute("aria-checked", "true")
    expect(
      screen.getByRole("menuitemradio", { name: "Français" })
    ).toHaveAttribute("aria-checked", "false")
  })

  it("stores the language picked and closes the menu", async () => {
    const user = await openSwitcher()

    await user.click(
      await screen.findByRole("menuitemradio", { name: "Français" })
    )

    expect(setLocale).toHaveBeenCalledExactlyOnceWith("fr")
    await waitFor(() =>
      expect(screen.queryByRole("menu")).not.toBeInTheDocument()
    )
  })

  it("does nothing when the current language is picked again", async () => {
    const user = await openSwitcher()

    await user.click(
      await screen.findByRole("menuitemradio", { name: "English" })
    )

    expect(setLocale).not.toHaveBeenCalled()
  })
})
