// @vitest-environment node
import { afterEach, describe, expect, it, vi } from "vitest"
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

function upload(image?: File) {
  const form = new FormData()
  if (image) form.append("image", image)
  return new Request("http://localhost/api/users/me/avatar", {
    method: "POST",
    body: form,
  })
}

afterEach(() => {
  backendPost.mockReset()
  backendDelete.mockReset()
})

describe("POST /api/users/me/avatar", () => {
  it("forwards the picture of the signed-in user, named by their token alone", async () => {
    backendPost.mockResolvedValue({ data: {}, response: new Response(null) })

    await POST(upload(new File(["gif"], "me.gif", { type: "image/gif" })))

    const [path, options] = backendPost.mock.calls[0]
    expect(path).toBe("/api/users/me/avatar")
    expect(options.params).toBeUndefined()
    const form: FormData = options.bodySerializer(options.body)
    expect((form.get("image") as File).name).toBe("me.gif")
  })

  it("refuses a form without a picture, without asking the backend", async () => {
    const response = await POST(upload())

    expect(response.status).toBe(400)
    expect(backendPost).not.toHaveBeenCalled()
  })
})

describe("DELETE /api/users/me/avatar", () => {
  it("asks the backend to take the picture away", async () => {
    backendDelete.mockResolvedValue({ data: {}, response: new Response(null) })

    const response = await DELETE()

    expect(backendDelete).toHaveBeenCalledWith("/api/users/me/avatar")
    expect(response.status).toBe(200)
  })
})
