// @vitest-environment node
import { afterEach, describe, expect, it, vi } from "vitest"
import { MAX_IMAGE_BYTES } from "@/features/images/lib/image-file"
import { DELETE, POST } from "./route"

vi.mock("server-only", () => ({}))

const backendPost = vi.fn()
const backendDelete = vi.fn()

vi.mock("@/libs/api/client", () => ({
  getServerApiClient: async () => ({
    POST: backendPost,
    DELETE: backendDelete,
  }),
}))

const ARTICLE_ID = "11111111-1111-4111-8111-111111111111"

function context(id: string) {
  return { params: Promise.resolve({ id }) }
}

function upload(image?: File) {
  const form = new FormData()
  if (image) form.append("image", image)
  return new Request(`http://localhost/api/articles/${ARTICLE_ID}/image`, {
    method: "POST",
    body: form,
  })
}

afterEach(() => {
  backendPost.mockReset()
  backendDelete.mockReset()
})

describe("POST /api/articles/[id]/image", () => {
  it("forwards the image as the multipart form the backend reads", async () => {
    backendPost.mockResolvedValue({ data: {}, response: new Response(null) })

    await POST(
      upload(new File(["png"], "cover.png", { type: "image/png" })),
      context(ARTICLE_ID)
    )

    expect(backendPost).toHaveBeenCalledOnce()
    const [path, options] = backendPost.mock.calls[0]
    expect(path).toBe("/api/editor/articles/{id}/image")
    expect(options.params.path).toEqual({ id: ARTICLE_ID })
    const form: FormData = options.bodySerializer(options.body)
    const image = form.get("image") as File
    expect(image.name).toBe("cover.png")
    expect(await image.text()).toBe("png")
  })

  it("answers what the backend answers", async () => {
    backendPost.mockResolvedValue({
      error: { error: "The file is not a JPEG, PNG, WebP or GIF image" },
      response: new Response(null, { status: 400 }),
    })

    const response = await POST(
      upload(new File(["%PDF"], "cover.png", { type: "image/png" })),
      context(ARTICLE_ID)
    )

    expect(response.status).toBe(400)
  })

  it("refuses a form without an image, without asking the backend", async () => {
    const response = await POST(upload(), context(ARTICLE_ID))

    expect(response.status).toBe(400)
    expect(backendPost).not.toHaveBeenCalled()
  })

  it("refuses an empty file or one over 8 MB, without asking the backend", async () => {
    const empty = await POST(
      upload(new File([], "cover.png", { type: "image/png" })),
      context(ARTICLE_ID)
    )
    const heavy = await POST(
      upload(
        new File([new Uint8Array(MAX_IMAGE_BYTES + 1)], "cover.png", {
          type: "image/png",
        })
      ),
      context(ARTICLE_ID)
    )

    expect([empty.status, heavy.status]).toEqual([400, 400])
    expect(backendPost).not.toHaveBeenCalled()
  })

  it("refuses an id that is not a backend identifier, without asking the backend", async () => {
    const response = await POST(
      upload(new File(["png"], "cover.png", { type: "image/png" })),
      context("nope")
    )

    expect(response.status).toBe(404)
    expect(backendPost).not.toHaveBeenCalled()
  })
})

describe("DELETE /api/articles/[id]/image", () => {
  it("asks the backend to take the cover away", async () => {
    backendDelete.mockResolvedValue({ data: {}, response: new Response(null) })

    await DELETE(
      new Request(`http://localhost/api/articles/${ARTICLE_ID}/image`, {
        method: "DELETE",
      }),
      context(ARTICLE_ID)
    )

    const [path, options] = backendDelete.mock.calls[0]
    expect(path).toBe("/api/editor/articles/{id}/image")
    expect(options.params.path).toEqual({ id: ARTICLE_ID })
  })
})
