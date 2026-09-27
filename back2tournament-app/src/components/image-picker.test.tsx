import { afterEach, describe, expect, it, vi } from "vitest"
import userEvent from "@testing-library/user-event"
import { renderWithProviders, screen, waitFor } from "@tests/test-utils"
import { MAX_IMAGE_BYTES } from "@/features/images/lib/image-file"
import { ImagePicker } from "./image-picker"

const refresh = vi.fn()
vi.mock("next/navigation", () => ({ useRouter: () => ({ refresh }) }))

const add = vi.fn()
vi.mock("@/components/ui/toast", () => ({
  toast: { add: (...args: unknown[]) => add(...args) },
}))

const ENDPOINT = "/api/games/11111111-1111-4111-8111-111111111111/image"
const IMAGE =
  "http://localhost:3902/games/0f8fad5b-d9cb-469f-a165-70867728950e.webp"

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

function fileInput(): HTMLInputElement {
  const input = document.querySelector<HTMLInputElement>('input[type="file"]')
  if (!input) throw new Error("no file input")
  return input
}

function png(name = "cover.png"): File {
  return new File(["png"], name, { type: "image/png" })
}

afterEach(() => {
  vi.unstubAllGlobals()
  vi.clearAllMocks()
})

describe("ImagePicker", () => {
  it("sends the chosen image, then reloads the page to show it", async () => {
    const fetchMock = backendAnswers(200, { image: IMAGE })
    renderWithProviders(
      <ImagePicker endpoint={ENDPOINT} name="Street Fighter 6" />
    )

    expect(fileInput()).toHaveAttribute(
      "accept",
      "image/jpeg,image/png,image/webp,image/gif"
    )
    await userEvent.upload(fileInput(), png())

    await waitFor(() => expect(fetchMock).toHaveBeenCalledTimes(1))
    const [url, init] = fetchMock.mock.calls[0]!
    expect(url).toBe(ENDPOINT)
    expect(init?.method).toBe("POST")
    // A form, whose multipart boundary the browser sets itself.
    expect(init?.headers).toBeUndefined()
    const form = init?.body
    expect(form).toBeInstanceOf(FormData)
    expect(((form as FormData).get("image") as File).name).toBe("cover.png")
    await waitFor(() => expect(refresh).toHaveBeenCalled())
    expect(add).toHaveBeenCalledWith(
      expect.objectContaining({ type: "success", title: "Image saved" })
    )
  })

  it("does not send a file that is not an image", async () => {
    const fetchMock = backendAnswers(200, {})
    renderWithProviders(
      <ImagePicker endpoint={ENDPOINT} name="Street Fighter 6" />
    )

    // The file dialog may be switched to show every file.
    await userEvent
      .setup({ applyAccept: false })
      .upload(
        fileInput(),
        new File(["%PDF"], "rules.pdf", { type: "application/pdf" })
      )

    expect(add).toHaveBeenCalledWith(
      expect.objectContaining({
        type: "error",
        description: "Choose a JPEG, PNG, WebP or GIF image.",
      })
    )
    expect(fetchMock).not.toHaveBeenCalled()
  })

  it("does not send an image over 8 MB", async () => {
    const fetchMock = backendAnswers(200, {})
    renderWithProviders(
      <ImagePicker endpoint={ENDPOINT} name="Street Fighter 6" />
    )
    const heavy = png()
    Object.defineProperty(heavy, "size", { value: MAX_IMAGE_BYTES + 1 })

    await userEvent.upload(fileInput(), heavy)

    expect(add).toHaveBeenCalledWith(
      expect.objectContaining({
        type: "error",
        description: "The image weighs more than 8 MB.",
      })
    )
    expect(fetchMock).not.toHaveBeenCalled()
  })

  it("says why the API refused an image", async () => {
    backendAnswers(400, { message: "The image cannot be read" })
    renderWithProviders(
      <ImagePicker endpoint={ENDPOINT} name="Street Fighter 6" />
    )

    await userEvent.upload(fileInput(), png())

    await waitFor(() =>
      expect(add).toHaveBeenCalledWith(
        expect.objectContaining({
          type: "error",
          title: "Could not send the image",
          description:
            "The image could not be read: it must be 16 pixels a side at least and 40 megapixels at most.",
        })
      )
    )
    expect(refresh).not.toHaveBeenCalled()
  })

  it("shows the image and takes it away once confirmed", async () => {
    const fetchMock = backendAnswers(200, { image: null })
    renderWithProviders(
      <ImagePicker endpoint={ENDPOINT} image={IMAGE} name="Street Fighter 6" />
    )

    expect(
      screen.getByRole("img", { name: "Image of Street Fighter 6" })
    ).toHaveAttribute("src", IMAGE)
    await userEvent.click(
      screen.getByRole("button", { name: "Remove the image" })
    )

    // Nothing is sent before the confirmation.
    expect(fetchMock).not.toHaveBeenCalled()
    await userEvent.click(screen.getByRole("button", { name: "Confirm" }))

    await waitFor(() => expect(fetchMock).toHaveBeenCalledTimes(1))
    const [url, init] = fetchMock.mock.calls[0]!
    expect(url).toBe(ENDPOINT)
    expect(init?.method).toBe("DELETE")
    await waitFor(() => expect(refresh).toHaveBeenCalled())
  })

  it("fits in a table row with buttons named after what the image illustrates", async () => {
    renderWithProviders(
      <ImagePicker
        endpoint={ENDPOINT}
        image={IMAGE}
        name="Street Fighter 6"
        shape="square"
        compact
      />
    )

    expect(
      screen.getByRole("button", {
        name: "Change the image of Street Fighter 6",
      })
    ).toBeInTheDocument()
    await userEvent.click(
      screen.getByRole("button", {
        name: "Remove the image of Street Fighter 6",
      })
    )
    expect(
      screen.getByRole("button", {
        name: "Confirm removing the image of Street Fighter 6",
      })
    ).toBeInTheDocument()
  })
})
