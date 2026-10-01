import { describe, expect, it, vi } from "vitest"
import userEvent from "@testing-library/user-event"
import { renderWithProviders, screen } from "@tests/test-utils"
import { PasswordConfirmation } from "./password-confirmation"

vi.mock("next/navigation", () => ({
  useRouter: () => ({ refresh: vi.fn(), replace: vi.fn() }),
}))

function renderConfirmation(onConfirm = vi.fn(), pending = false) {
  renderWithProviders(
    <PasswordConfirmation
      trigger="Delete it"
      confirm="Delete for good"
      pending={pending}
      onConfirm={onConfirm}
    />
  )
  return onConfirm
}

describe("PasswordConfirmation", () => {
  it("asks for the password before confirming", async () => {
    const onConfirm = renderConfirmation()

    expect(screen.queryByLabelText("Confirm with your password")).toBeNull()
    await userEvent.click(screen.getByRole("button", { name: "Delete it" }))

    const confirm = screen.getByRole("button", { name: "Delete for good" })
    expect(confirm).toBeDisabled()

    await userEvent.type(
      screen.getByLabelText("Confirm with your password"),
      "Secret1!"
    )
    await userEvent.click(confirm)

    expect(onConfirm).toHaveBeenCalledWith("Secret1!")
  })

  it("forgets the typed password when cancelled", async () => {
    const onConfirm = renderConfirmation()

    await userEvent.click(screen.getByRole("button", { name: "Delete it" }))
    await userEvent.type(
      screen.getByLabelText("Confirm with your password"),
      "Secret1!"
    )
    await userEvent.click(screen.getByRole("button", { name: "Cancel" }))
    await userEvent.click(screen.getByRole("button", { name: "Delete it" }))

    expect(screen.getByLabelText("Confirm with your password")).toHaveValue("")
    expect(onConfirm).not.toHaveBeenCalled()
  })

  it("holds the confirmation while the deletion runs", async () => {
    renderConfirmation(vi.fn(), true)

    await userEvent.click(screen.getByRole("button", { name: "Delete it" }))
    await userEvent.type(
      screen.getByLabelText("Confirm with your password"),
      "Secret1!"
    )

    expect(
      screen.getByRole("button", { name: /Delete for good/ })
    ).toBeDisabled()
    expect(screen.getByRole("status", { name: "Loading" })).toBeVisible()
    expect(screen.getByRole("button", { name: "Cancel" })).toBeDisabled()
  })
})
