import { describe, expect, it } from "vitest"
import { imageFileProblem, MAX_IMAGE_BYTES, toImageForm } from "./image-file"

describe("imageFileProblem", () => {
  it("lets a JPEG, PNG or WebP image of 8 MB at most through", () => {
    for (const type of ["image/jpeg", "image/png", "image/webp"]) {
      expect(imageFileProblem({ type, size: 1024 })).toBeNull()
    }
    expect(
      imageFileProblem({ type: "image/png", size: MAX_IMAGE_BYTES })
    ).toBeNull()
  })

  it("stops any other type, an empty file and a file over 8 MB", () => {
    expect(imageFileProblem({ type: "image/gif", size: 1024 })).toBe("type")
    expect(imageFileProblem({ type: "image/svg+xml", size: 1024 })).toBe("type")
    expect(imageFileProblem({ type: "application/pdf", size: 1024 })).toBe(
      "type"
    )
    expect(imageFileProblem({ type: "", size: 1024 })).toBe("type")
    expect(imageFileProblem({ type: "image/png", size: 0 })).toBe("empty")
    expect(
      imageFileProblem({ type: "image/jpeg", size: MAX_IMAGE_BYTES + 1 })
    ).toBe("size")
  })
})

describe("toImageForm", () => {
  it("puts the image under the field the API reads", () => {
    const image = new File(["png"], "cover.png", { type: "image/png" })

    const form = toImageForm({ image })

    expect(form.get("image")).toBeInstanceOf(File)
    expect((form.get("image") as File).name).toBe("cover.png")
  })
})
