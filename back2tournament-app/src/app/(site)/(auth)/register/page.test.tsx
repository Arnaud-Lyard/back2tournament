import { screen } from "@testing-library/react"
import userEvent from "@testing-library/user-event"
import { afterEach, describe, expect, it, vi } from "vitest"
import { renderWithProviders } from "@tests/test-utils"
import RegisterPage from "./page"

function mockFetch(response: Response) {
  const fetchMock = vi.fn().mockResolvedValue(response)
  vi.stubGlobal("fetch", fetchMock)
  return fetchMock
}

async function fillAndSubmit(password = "Password1!") {
  const user = userEvent.setup()
  renderWithProviders(<RegisterPage />)

  await user.type(screen.getByLabelText("Email"), "jane@example.com")
  await user.type(screen.getByLabelText("Username"), "jane_doe")
  await user.type(screen.getByLabelText("Password"), password)
  await user.type(screen.getByLabelText("Confirm password"), password)
  await user.click(screen.getByRole("button", { name: "Create account" }))

  return user
}

afterEach(() => {
  vi.unstubAllGlobals()
  vi.restoreAllMocks()
})

describe("RegisterPage", () => {
  it("explains each password rule the backend enforces, before sending", async () => {
    const fetchMock = mockFetch(Response.json({}))

    await fillAndSubmit("password")

    expect(screen.getByText("At least one digit.")).toBeInTheDocument()
    expect(
      screen.getByText("At least one uppercase letter.")
    ).toBeInTheDocument()
    expect(fetchMock).not.toHaveBeenCalled()
  })

  it("words a taken username under its field, in the user's language", async () => {
    vi.spyOn(console, "error").mockImplementation(() => {})
    mockFetch(
      Response.json(
        { message: "username already used", code: "usernameTaken" },
        { status: 409 }
      )
    )

    const user = await fillAndSubmit()

    expect(
      await screen.findByText("This username is already taken.")
    ).toBeInTheDocument()
    expect(screen.queryByText("username already used")).not.toBeInTheDocument()

    await user.type(screen.getByLabelText("Username"), "2")
    expect(
      screen.queryByText("This username is already taken.")
    ).not.toBeInTheDocument()
  })

  it("words a taken email under its field", async () => {
    vi.spyOn(console, "error").mockImplementation(() => {})
    mockFetch(
      Response.json(
        { message: "email already used", code: "emailTaken" },
        { status: 409 }
      )
    )

    await fillAndSubmit()

    expect(
      await screen.findByText("This email address is already in use.")
    ).toBeInTheDocument()
  })
})
