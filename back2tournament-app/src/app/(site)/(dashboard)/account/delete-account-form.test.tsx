import { afterEach, describe, expect, it, vi } from "vitest"
import userEvent from "@testing-library/user-event"
import { renderWithProviders, screen, waitFor } from "@tests/test-utils"
import { DeleteAccountForm } from "./delete-account-form"

const refresh = vi.fn()
const replace = vi.fn()
vi.mock("next/navigation", () => ({ useRouter: () => ({ refresh, replace }) }))

const add = vi.fn()
vi.mock("@/components/ui/toast", () => ({
  toast: { add: (...args: unknown[]) => add(...args) },
}))

function backendAnswers(status: number, body: unknown) {
  const fetchMock = vi.fn(
    async (_input: string, _init?: RequestInit) =>
      new Response(JSON.stringify(body), {
        status,
        headers: { "Content-Type": "application/json" },
      })
  )
  vi.stubGlobal("fetch", fetchMock)
  return fetchMock
}

async function deleteWith(password: string) {
  renderWithProviders(<DeleteAccountForm />)

  await userEvent.click(
    screen.getByRole("button", { name: "Delete my account" })
  )
  await userEvent.type(
    screen.getByLabelText("Confirm with your password"),
    password
  )
  await userEvent.click(screen.getByRole("button", { name: "Delete for good" }))
}

afterEach(() => {
  vi.unstubAllGlobals()
  vi.clearAllMocks()
})

describe("DeleteAccountForm", () => {
  it("deletes the account with the password and leaves for the home page", async () => {
    const fetchMock = backendAnswers(200, { deleted: true })

    await deleteWith("Secret1!")

    await waitFor(() => expect(replace).toHaveBeenCalledWith("/"))
    const [url, init] = fetchMock.mock.calls[0]!
    expect(url).toBe("/api/users/me/deletion")
    expect(init?.method).toBe("POST")
    expect(JSON.parse(String(init?.body))).toEqual({ password: "Secret1!" })
    expect(refresh).toHaveBeenCalled()
    expect(add).toHaveBeenCalledWith(
      expect.objectContaining({
        type: "success",
        title: "Your account was deleted",
      })
    )
  })

  it.each([
    [
      403,
      "the password does not match",
      "wrongPassword",
      "The password does not match.",
    ],
    [
      409,
      "you lead a clan: dissolve it first",
      "accountLeadsClan",
      "You lead a clan: dissolve it first from its page.",
    ],
    [
      409,
      "you organize a tournament still open for registration: start or cancel it first",
      "accountOrganizesTournament",
      "You organize a tournament still open for registration: start or cancel it first.",
    ],
  ])(
    "explains a %s refusal: %s",
    async (status, message, code, description) => {
      backendAnswers(status, { message, code })

      await deleteWith("Secret1!")

      await waitFor(() =>
        expect(add).toHaveBeenCalledWith(
          expect.objectContaining({ type: "error", description })
        )
      )
      expect(replace).not.toHaveBeenCalled()
    }
  )
})
