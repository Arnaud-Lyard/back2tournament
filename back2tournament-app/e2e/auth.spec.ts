import { expect, test } from "@playwright/test"

function base64url(value: object) {
  return Buffer.from(JSON.stringify(value)).toString("base64url")
}

// A structurally valid but unsigned JWT — decodeSymfonyJwt() only decodes,
// never verifies, so this is enough for the login route's own logic.
const FAKE_JWT = [
  base64url({ alg: "none", typ: "JWT" }),
  base64url({
    username: "demo",
    roles: ["ROLE_USER"],
    exp: Math.floor(Date.now() / 1000) + 3600,
  }),
  "signature",
].join(".")

// Stubs our own /api/auth/login route (browser-facing), including the
// Set-Cookie header it would normally set after proxying to Symfony — no
// real backend needed for this test.
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

  // Target inputs by id rather than translated label text — the app
  // defaults to the French locale, so English label regexes would miss.
  await page.goto("/login")
  await page.locator("#username").fill("demo")
  await page.locator("#password").fill("password123")
  await page.locator('button[type="submit"]').click()

  await expect(page).toHaveURL("/")
})
