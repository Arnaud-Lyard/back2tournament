import { screen } from "@testing-library/react"
import { describe, expect, it } from "vitest"
import { mapSymfonyRoles } from "@/features/auth/rbac/map-symfony-roles"
import type { AuthUser } from "@/features/auth/types"
import { renderWithProviders } from "@tests/test-utils"
import { SiteHeader } from "./site-header"

function userWith(roles: string[]): AuthUser {
  return {
    username: "demo",
    verified: true,
    avatar: null,
    ...mapSymfonyRoles(roles),
    playersByGame: {},
  }
}

describe("SiteHeader", () => {
  it("offers a guest to log in or sign up, and hides member links", () => {
    renderWithProviders(<SiteHeader />)

    expect(screen.getByRole("link", { name: "Login" })).toHaveAttribute(
      "href",
      "/login"
    )
    expect(screen.getByRole("link", { name: "Sign up" })).toBeInTheDocument()
    expect(
      screen.queryByRole("link", { name: "My fights" })
    ).not.toBeInTheDocument()
  })

  it("keeps a player's account links out of the main nav", () => {
    renderWithProviders(<SiteHeader />, { user: userWith(["ROLE_USER"]) })

    expect(screen.getByRole("link", { name: "Home" })).toHaveAttribute(
      "href",
      "/"
    )
    expect(screen.getByRole("link", { name: "Blog" })).toHaveAttribute(
      "href",
      "/blog"
    )
    for (const name of [
      "My fights",
      "Challenge a player",
      "Player profile",
      "Backoffice",
    ]) {
      expect(screen.queryByRole("link", { name })).not.toBeInTheDocument()
    }
    expect(
      screen.getByRole("button", { name: "Account menu for demo" })
    ).toBeInTheDocument()
  })

  it("always offers the theme toggle and the language switcher", () => {
    renderWithProviders(<SiteHeader />)

    expect(
      screen.getByRole("button", { name: "Toggle theme" })
    ).toBeInTheDocument()
    expect(
      screen.getByRole("button", { name: "Change language" })
    ).toBeInTheDocument()
  })
})
