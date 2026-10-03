// @vitest-environment node
import { afterEach, describe, expect, it, vi } from "vitest"
import { MAX_IMAGE_BYTES } from "@/features/images/lib/image-file"
import {
  formBody,
  SESSION_TOKEN,
  SYMFONY,
  symfonyAnswers,
} from "@tests/symfony-api"
import { DELETE, POST } from "./route"

vi.mock("server-only", () => ({}))

vi.mock("next/headers", () => ({
  cookies: async () => ({ get: () => ({ value: SESSION_TOKEN }) }),
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
  vi.unstubAllGlobals()
})

describe("POST /api/articles/[id]/image", () => {
  it("forwards the image as the multipart form the backend reads", async () => {
    const requests = symfonyAnswers(200)

    await POST(
      upload(new File(["png"], "cover.png", { type: "image/png" })),
      context(ARTICLE_ID)
    )

    expect(requests).toHaveLength(1)
    expect(requests[0]).toMatchObject({
      url: `${SYMFONY}/api/editor/articles/${ARTICLE_ID}/image`,
      method: "POST",
      authorization: `Bearer ${SESSION_TOKEN}`,
    })
    const image = formBody(requests[0]).get("image") as File
    expect(image.name).toBe("cover.png")
    expect(await image.text()).toBe("png")
  })

  it("answers what the backend answers", async () => {
    symfonyAnswers(400, { error: "The file is not a JPEG, PNG or WebP image" })

    const response = await POST(
      upload(new File(["%PDF"], "cover.png", { type: "image/png" })),
      context(ARTICLE_ID)
    )

    expect(response.status).toBe(400)
    expect(await response.json()).toEqual({
      message: "The file is not a JPEG, PNG or WebP image",
    })
  })

  it("refuses a form without an image, without asking the backend", async () => {
    const requests = symfonyAnswers(200)

    const response = await POST(upload(), context(ARTICLE_ID))

    expect(response.status).toBe(400)
    expect(requests).toHaveLength(0)
  })

  it("refuses an empty file or one over 8 MB, without asking the backend", async () => {
    const requests = symfonyAnswers(200)
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
    expect(requests).toHaveLength(0)
  })

  it("refuses an id that is not a backend identifier, without asking the backend", async () => {
    const requests = symfonyAnswers(200)

    const response = await POST(
      upload(new File(["png"], "cover.png", { type: "image/png" })),
      context("nope")
    )

    expect(response.status).toBe(404)
    expect(requests).toHaveLength(0)
  })
})

describe("DELETE /api/articles/[id]/image", () => {
  it("asks the backend to take the cover away", async () => {
    const requests = symfonyAnswers(200)

    await DELETE(
      new Request(`http://localhost/api/articles/${ARTICLE_ID}/image`, {
        method: "DELETE",
      }),
      context(ARTICLE_ID)
    )

    expect(requests[0]).toMatchObject({
      url: `${SYMFONY}/api/editor/articles/${ARTICLE_ID}/image`,
      method: "DELETE",
      authorization: `Bearer ${SESSION_TOKEN}`,
    })
  })
})
