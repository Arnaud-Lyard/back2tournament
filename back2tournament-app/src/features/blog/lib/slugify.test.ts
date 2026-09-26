import { describe, expect, it } from "vitest"
import { createCategorySchema } from "../schemas/create-category.schema"
import { slugify } from "./slugify"

describe("slugify", () => {
  it("drops accents, case and punctuation", () => {
    expect(slugify("Actualités & E-sport !")).toBe("actualites-e-sport")
  })

  it("collapses separators and trims them from both ends", () => {
    expect(slugify("  --Street   Fighter 6--  ")).toBe("street-fighter-6")
  })

  it("produces slugs the category schema accepts", () => {
    const name = "Événements à venir"
    expect(
      createCategorySchema.safeParse({ name, slug: slugify(name) }).success
    ).toBe(true)
  })
})
