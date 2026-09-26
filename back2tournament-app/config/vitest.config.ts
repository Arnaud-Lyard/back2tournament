import { fileURLToPath } from "node:url"
import react from "@vitejs/plugin-react"
import { defineConfig } from "vitest/config"

export default defineConfig({
  plugins: [react()],
  test: {
    environment: "jsdom",
    globals: true,
    exclude: ["**/node_modules/**", "e2e/**"],
    setupFiles: [fileURLToPath(new URL("../tests/setup.ts", import.meta.url))],
    env: {
      SYMFONY_API_URL: "https://localhost",
      NEXT_PUBLIC_SITE_URL: "http://localhost:3000",
      NEXT_PUBLIC_APP_ENV: "development",
    },
  },
  resolve: {
    tsconfigPaths: true,
  },
})
