import { describe, expect, it } from "vitest"
import { mapSymfonyRoles } from "./map-symfony-roles"

describe("mapSymfonyRoles", () => {
  it("gives admin precedence over other roles", () => {
    const { role } = mapSymfonyRoles(["ROLE_USER", "ROLE_EDITOR", "ROLE_ADMIN"])
    expect(role).toBe("admin")
  })

  it("defaults to user for empty or unrecognized roles", () => {
    expect(mapSymfonyRoles([]).role).toBe("user")
    expect(mapSymfonyRoles(["ROLE_UNKNOWN"]).role).toBe("user")
  })

  it("grants editor permissions that plain users don't have", () => {
    const editor = mapSymfonyRoles(["ROLE_EDITOR"])
    const user = mapSymfonyRoles(["ROLE_USER"])

    expect(editor.permissions).toContain("article:create" as never)
    expect(editor.permissions).not.toEqual(user.permissions)
  })

  it("opens the backoffice to editors and admins only", () => {
    const canAccess = (roles: string[]) =>
      mapSymfonyRoles(roles).permissions.includes("backoffice:access")

    expect(canAccess(["ROLE_ADMIN"])).toBe(true)
    expect(canAccess(["ROLE_EDITOR"])).toBe(true)
    expect(canAccess(["ROLE_USER"])).toBe(false)
  })

  it("keeps game and category management to admins", () => {
    const editor = mapSymfonyRoles(["ROLE_EDITOR"]).permissions
    const admin = mapSymfonyRoles(["ROLE_ADMIN"]).permissions

    expect(editor).not.toContain("game:manage")
    expect(editor).not.toContain("category:manage")
    expect(admin).toEqual(
      expect.arrayContaining(["game:manage", "category:manage"])
    )
  })
})
