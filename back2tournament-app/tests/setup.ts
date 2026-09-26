import "@testing-library/jest-dom/vitest"
import { vi } from "vitest"

// AuthProvider calls useRouter() and the navigation components usePathname();
// plain RTL renders have no App Router context mounted, so this needs a global
// mock for any test that renders them.
vi.mock("next/navigation", () => ({
  useRouter: () => ({
    push: vi.fn(),
    replace: vi.fn(),
    refresh: vi.fn(),
    back: vi.fn(),
    forward: vi.fn(),
    prefetch: vi.fn(),
  }),
  usePathname: () => "/",
}))

// jsdom implements neither of these; next-themes reads matchMedia and several
// shadcn/base-ui primitives use ResizeObserver — without these, component
// tests touching the theme provider or a popover/dialog throw.
if (!window.matchMedia) {
  window.matchMedia = (query: string) => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: () => {},
    removeListener: () => {},
    addEventListener: () => {},
    removeEventListener: () => {},
    dispatchEvent: () => false,
  })
}

if (!window.ResizeObserver) {
  window.ResizeObserver = class ResizeObserver {
    observe() {}
    unobserve() {}
    disconnect() {}
  }
}
