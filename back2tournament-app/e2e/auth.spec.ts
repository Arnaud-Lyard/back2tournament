import { expect, test } from "@playwright/test"

function base64url(value: object) {
  return Buffer.from(JSON.stringify(value)).toString("base64url")
}

const FAKE_JWT = [
  base64url({ alg: "none", typ: "JWT" }),
  base64url({
    username: "demo",
    roles: ["ROLE_USER"],
    exp: Math.floor(Date.now() / 1000) + 3600,
  }),
  "signature",
].join(".")

test("login redirects to home on success", async ({ page }) => {
  await page.route("**/api/auth/login", async (route) => {
    await route.fulfill({
      status: 200,
      contentType: "application/json",
      headers: { "set-cookie": `b2t_session=${FAKE_JWT}; Path=/` },
      body: JSON.stringify({
        username: "demo",
        role: "user",
        permissions: [],
        verified: true,
      }),
    })
  })

  await page.goto("/login")
  await page.locator("#username").fill("demo")
  await page.locator("#password").fill("password123")
  await page.locator('button[type="submit"]').click()

  await expect(page).toHaveURL("/")
})
