import { afterEach, describe, expect, it, vi } from "vitest"
import userEvent from "@testing-library/user-event"
import { renderWithProviders, screen, waitFor } from "@tests/test-utils"
import type { AuthUser } from "@/features/auth/types"
import { CommentForm } from "./comment-form"

const refresh = vi.fn()
vi.mock("next/navigation", () => ({ useRouter: () => ({ refresh }) }))

const add = vi.fn()
vi.mock("@/components/ui/toast", () => ({
  toast: { add: (...args: unknown[]) => add(...args) },
}))

const ARTICLE_ID = "11111111-1111-4111-8111-111111111111"

const reader: AuthUser = {
  username: "jane",
  role: "user",
  permissions: [],
  verified: true,
  playersByGame: {},
}

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

afterEach(() => {
  vi.unstubAllGlobals()
  vi.clearAllMocks()
})

describe("CommentForm", () => {
  it("asks a visitor to sign in rather than showing a box they cannot post", () => {
    renderWithProviders(<CommentForm articleId={ARTICLE_ID} />)

    expect(screen.getByRole("link", { name: "Sign in" })).toHaveAttribute(
      "href",
      "/login"
    )
    expect(screen.queryByRole("textbox")).not.toBeInTheDocument()
  })

  it("posts the comment under the article, and nothing identifying with it", async () => {
    const fetchMock = backendAnswers(200, { id: { value: "comment" } })
    renderWithProviders(<CommentForm articleId={ARTICLE_ID} />, {
      user: reader,
    })

    await userEvent.type(screen.getByRole("textbox"), "Great article!")
    await userEvent.click(screen.getByRole("button", { name: "Post" }))

    await waitFor(() => expect(fetchMock).toHaveBeenCalledOnce())
    const [url, init] = fetchMock.mock.calls[0]
    expect(url).toBe("/api/comments")
    expect(JSON.parse(String(init?.body))).toEqual({
      articleId: ARTICLE_ID,
      message: "Great article!",
    })
    await waitFor(() => expect(refresh).toHaveBeenCalled())
  })

  it("empties the box once the comment is posted", async () => {
    backendAnswers(200, { id: { value: "comment" } })
    renderWithProviders(<CommentForm articleId={ARTICLE_ID} />, {
      user: reader,
    })

    await userEvent.type(screen.getByRole("textbox"), "Great article!")
    await userEvent.click(screen.getByRole("button", { name: "Post" }))

    await waitFor(() => expect(screen.getByRole("textbox")).toHaveValue(""))
  })

  it("refuses an empty comment before asking the server", async () => {
    const fetchMock = backendAnswers(200, {})
    renderWithProviders(<CommentForm articleId={ARTICLE_ID} />, {
      user: reader,
    })

    await userEvent.click(screen.getByRole("button", { name: "Post" }))

    expect(fetchMock).not.toHaveBeenCalled()
    expect(
      await screen.findByText("This field is required.")
    ).toBeInTheDocument()
  })
})
