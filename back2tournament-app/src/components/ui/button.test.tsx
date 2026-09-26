import { fireEvent, screen } from "@testing-library/react"
import userEvent from "@testing-library/user-event"
import { describe, expect, it, vi } from "vitest"
import { renderWithProviders } from "@tests/test-utils"
import { Button } from "./button"

describe("Button", () => {
  it("fires the click handler", async () => {
    const onClick = vi.fn()
    renderWithProviders(<Button onClick={onClick}>Click me</Button>)

    await userEvent.click(screen.getByRole("button", { name: "Click me" }))
    expect(onClick).toHaveBeenCalledTimes(1)
  })

  it("does not fire when disabled", () => {
    const onClick = vi.fn()
    renderWithProviders(
      <Button onClick={onClick} disabled>
        Click me
      </Button>
    )

    fireEvent.click(screen.getByRole("button", { name: "Click me" }))
    expect(onClick).not.toHaveBeenCalled()
  })

  it("applies the default variant class", () => {
    renderWithProviders(<Button>Click me</Button>)
    expect(screen.getByRole("button", { name: "Click me" })).toHaveClass(
      "bg-primary"
    )
  })
})
