// @vitest-environment node
import { afterEach, describe, expect, it, vi } from "vitest"
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

function upload(image?: File) {
  const form = new FormData()
  if (image) form.append("image", image)
  return new Request("http://localhost/api/users/me/avatar", {
    method: "POST",
    body: form,
  })
}

afterEach(() => {
  vi.unstubAllGlobals()
})

describe("POST /api/users/me/avatar", () => {
  it("forwards the picture of the signed-in user, named by their token alone", async () => {
    const requests = symfonyAnswers(200)

    await POST(upload(new File(["png"], "me.png", { type: "image/png" })))

    expect(requests[0]).toMatchObject({
      url: `${SYMFONY}/api/user/me/avatar`,
      method: "POST",
      authorization: `Bearer ${SESSION_TOKEN}`,
    })
    const image = formBody(requests[0]).get("image") as File
    expect(image.name).toBe("me.png")
    expect(await image.text()).toBe("png")
  })

  it("refuses a form without a picture, without asking the backend", async () => {
    const requests = symfonyAnswers(200)

    const response = await POST(upload())

    expect(response.status).toBe(400)
    expect(requests).toHaveLength(0)
  })
})

describe("DELETE /api/users/me/avatar", () => {
  it("asks the backend to take the picture away", async () => {
    const requests = symfonyAnswers(200)

    const response = await DELETE()

    expect(requests[0]).toMatchObject({
      url: `${SYMFONY}/api/user/me/avatar`,
      method: "DELETE",
      authorization: `Bearer ${SESSION_TOKEN}`,
    })
    expect(response.status).toBe(200)
  })
})
